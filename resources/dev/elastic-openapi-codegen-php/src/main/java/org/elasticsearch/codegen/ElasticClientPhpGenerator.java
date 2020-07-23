package org.elasticsearch.codegen;

import io.swagger.v3.oas.models.OpenAPI;
import io.swagger.v3.oas.models.Operation;
import io.swagger.v3.oas.models.Components;
import io.swagger.v3.oas.models.media.Content;
import io.swagger.v3.oas.models.media.Schema;
import io.swagger.v3.oas.models.media.MediaType;
import io.swagger.v3.oas.models.media.ObjectSchema;
import io.swagger.v3.oas.models.parameters.Parameter;
import io.swagger.v3.oas.models.parameters.QueryParameter;
import io.swagger.v3.oas.models.parameters.RequestBody;
import io.swagger.v3.oas.models.Paths;
import io.swagger.v3.oas.models.PathItem;

import java.util.*;
import java.util.stream.Collectors;
import java.util.function.Function;
import java.io.File;

import org.apache.commons.lang3.StringUtils;
import org.openapitools.codegen.*;
import org.openapitools.codegen.languages.PhpClientCodegen;
import org.openapitools.codegen.utils.ModelUtils;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;

public class ElasticClientPhpGenerator extends PhpClientCodegen implements CodegenConfig {
  public static final String GENERATOR_NAME         = "elastic-php-client";
  public static final String HELP_URL               = "helpUrl";
  public static final String COPYRIGHT              = "copyright";
  public static final String CLIENT_CLASS           = "clientClass";
  public static final String CLIENT_CLASS_QUALIFIER = "clientClassQualifier";

  public ElasticClientPhpGenerator() {
    super();

    cliOptions.add(new CliOption(HELP_URL, "Help URL"));
    cliOptions.add(new CliOption(COPYRIGHT, "Copyright"));
    cliOptions.add(new CliOption(CLIENT_CLASS, "Client file"));

    this.setTemplateDir(ElasticClientPhpGenerator.GENERATOR_NAME);
    this.setSrcBasePath("");
    this.embeddedTemplateDir = this.templateDir();

    this.apiDirName = "Endpoint";
    setApiPackage(getInvokerPackage() + "\\" + apiDirName);
    this.setParameterNamingConvention("camelCase");
  }

  @Override
  public void processOpts() {
    super.processOpts();

    String clientClass = (String) additionalProperties.getOrDefault(CLIENT_CLASS, "Client");
    additionalProperties.put(CLIENT_CLASS, clientClass);

    if (clientClass.startsWith("Abstract")) {
      additionalProperties.put(CLIENT_CLASS_QUALIFIER, "abstract");
    }

    this.resetTemplateFiles();

    supportingFiles.add(new SupportingFile("Client.mustache", "", clientClass.concat(".php")));
    supportingFiles.add(new SupportingFile("README.mustache", "", "README.md"));
  }

  @Override
  public CodegenType getTag() {
    return CodegenType.CLIENT;
  }

  @Override
  public String getName() {
    return ElasticClientPhpGenerator.GENERATOR_NAME;
  }

  @Override
  public String toApiName(String name) {
    return org.openapitools.codegen.utils.StringUtils.camelize(name);
  }

  @Override
  @SuppressWarnings("static-method")
  public void addOperationToGroup(String tag, String resourcePath, Operation operation, CodegenOperation co, Map<String, List<CodegenOperation>> operations) {
      String uniqueName = co.operationId;
      co.operationIdLowerCase = uniqueName.toLowerCase(Locale.ROOT);
      co.operationIdCamelCase = org.openapitools.codegen.utils.StringUtils.camelize(uniqueName);
      co.operationIdSnakeCase = org.openapitools.codegen.utils.StringUtils.underscore(uniqueName);

      if (co.vendorExtensions == null) {
        co.vendorExtensions = new HashMap<>();
      }

      co.vendorExtensions.put("x-operation-scope", co.vendorExtensions.getOrDefault("x-operation-scope", "public"));
      co.vendorExtensions.put("x-add-to-documentation", co.vendorExtensions.getOrDefault("x-operation-scope", "public").equals("public"));

      operations.put(uniqueName, Arrays.asList(co));
  }

  @Override
  @SuppressWarnings("rawtypes")
  public String getTypeDeclaration(Schema p) {
    if (ModelUtils.isArraySchema(p) || ModelUtils.isMapSchema(p)) {
      return "array";
    } else if (ModelUtils.isObjectSchema(p) || ModelUtils.isModel(p) || StringUtils.isNotBlank(p.get$ref())) {
      return "array";
    }

    return super.getTypeDeclaration(p);
  }

  @Override
  public String getTypeDeclaration(String name) {
    if (!languageSpecificPrimitives.contains(name)) {
      return "array";
    }

    return super.getTypeDeclaration(name);
  }

  @Override
  public void preprocessOpenAPI(OpenAPI openAPI) {
    super.preprocessOpenAPI(openAPI);

    Collection<PathItem> paths = openAPI.getPaths().values();

    Components components = openAPI.getComponents();
    Map<String, Schema> schemas = components.getSchemas();
    Map<String, RequestBody> requestBodies = openAPI.getComponents().getRequestBodies();

    for (PathItem pathItem : paths) {
        List<Operation> operations = pathItem.readOperations();
        for(Operation operation: operations) {
            if (operation.getParameters() != null) {
                List<Parameter> queryParameters = operation.getParameters()
                .stream()
                .filter(parameter -> parameter.getIn() == "query")
                .collect(Collectors.toList());

                if (! queryParameters.isEmpty()) {
                    String queryName = operation.getOperationId() + "Query";

                    Map<String, Schema> querySchemas = queryParameters
                                    .stream()
                                    .map(parameter -> {
                                        parameter.setName(org.openapitools.codegen.utils.StringUtils.camelize(parameter.getName(), true));

                                        return parameter;
                                    })
                                    .collect(Collectors.toMap(Parameter::getName, Parameter::getSchema));

                    List<String> requiredParameters = queryParameters
                        .stream()
                        .filter(parameter -> parameter.getRequired() == null || parameter.getRequired() == false)
                        .map(parameter -> org.openapitools.codegen.utils.StringUtils.camelize(parameter.getName(), true))
                        .collect(Collectors.toList());

                    ObjectSchema querySchema = new ObjectSchema();
                    querySchema.setProperties(querySchemas);
                    querySchema.setRequired(requiredParameters);

                    components.addSchemas(queryName, querySchema);
                }
            }

            RequestBody requestBody = operation.getRequestBody();

            if (requestBody == null) {
                continue;
            }

            String requestBodyRef = requestBody.get$ref();

            if (requestBodyRef == null) {
                continue;
            }

            String simpleRef = ModelUtils.getSimpleRef(requestBodyRef);
            String newSimpleRef = operation.getOperationId() + "Body";
            String newRequestBodyRef = requestBodyRef.replace(simpleRef, newSimpleRef);
            String newSchemaRef = newRequestBodyRef.replace("requestBodies", "schemas");

            Schema schema = schemas.get(simpleRef);
            schemas.remove(simpleRef);
            schemas.put(newSimpleRef, schema);

            RequestBody componentRequestBody = requestBodies.get(simpleRef);
            requestBodies.remove(simpleRef);
            requestBodies.put(newSimpleRef, componentRequestBody);

            ModelUtils.getSchemaFromRequestBody(componentRequestBody).set$ref(newSchemaRef);

            requestBody.set$ref(newRequestBodyRef);
        }
    }
  }

  @Override
  public void postProcessFile(File file, String fileType) {
      return;
  }

  private void resetTemplateFiles() {
    this.supportingFiles.clear();
    this.apiTemplateFiles.clear();
    this.apiTestTemplateFiles.clear();
    this.apiDocTemplateFiles.clear();
    this.modelTestTemplateFiles.clear();
    this.modelDocTemplateFiles.clear();

    apiTemplateFiles.put("api.mustache", ".php");
  }
}
