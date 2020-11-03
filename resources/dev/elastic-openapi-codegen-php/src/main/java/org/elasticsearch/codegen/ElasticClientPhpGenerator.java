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
import io.swagger.v3.oas.models.PathItem.HttpMethod;

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
    if (ModelUtils.isArraySchema(p)
        || ModelUtils.isMapSchema(p)
        || ModelUtils.isObjectSchema(p)
        || ModelUtils.isModel(p)
        || StringUtils.isNotBlank(p.get$ref())
    ) {
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

    Paths paths = openAPI.getPaths();

    Components components = openAPI.getComponents();

    for (String pathName : paths.keySet()) {
        PathItem pathItem = paths.get(pathName);
        Map<HttpMethod, Operation>  operations = pathItem.readOperationsMap();
        for(HttpMethod method: operations.keySet()) {
            Operation operation = operations.get(method);

            if (operation.getOperationId() == null) {
                operation.setOperationId(getOrGenerateOperationId(operation, pathName, method.toString().toLowerCase()));
            }

            if (operation.getParameters() != null) {
                this.createSchemaFromParameters(operation, components, "query");
            }

            if (operation.getRequestBody() != null) {
                this.createSchemaFromRequestBody(operation, components);
            }
        }
    }
  }

  @Override
  public List<CodegenParameter> fromRequestBodyToFormParameters(RequestBody body, Set<String> imports) {
        List<CodegenParameter> parameters = new ArrayList<CodegenParameter>();

        CodegenParameter parameter = CodegenModelFactory.newInstance(CodegenModelType.PARAMETER);
        parameter.baseType = body.getDescription();

        if (body.getRequired() != null) {
            parameter.required = body.getRequired();
        }

        parameters.add(parameter);

        return parameters;
  }

  @Override
  public String toApiImport(String name) {
      return apiPackage() + "\\" + name;
  }

  @Override
  public String toModelImport(String name) {
      if ("".equals(modelPackage())) {
          return name;
      } else {
          return modelPackage() + "\\" + name;
      }
  }

  private void createSchemaFromParameters(Operation operation, Components components, String inType) {
    List<Parameter> typeParameters = operation.getParameters()
        .stream()
        .filter(parameter -> (inType == "query" && parameter instanceof QueryParameter)
            || parameter.getIn() == inType
        )
        .collect(Collectors.toList());

        if (! typeParameters.isEmpty()) {
            String typeName = operation.getOperationId() + org.openapitools.codegen.utils.StringUtils.camelize(inType);

            Map<String, Schema> typeSchemas = typeParameters
                .stream()
                .collect(Collectors.toMap(Parameter::getName, Parameter::getSchema));

            List<String> requiredParameters = typeParameters
                .stream()
                .filter(parameter -> !(parameter.getRequired() == null || parameter.getRequired() == false))
                .map(parameter -> parameter.getName())
                .collect(Collectors.toList());

            ObjectSchema typeSchema = new ObjectSchema();
            typeSchema.setProperties(typeSchemas);
            typeSchema.setRequired(requiredParameters);

            components.addSchemas(typeName, typeSchema);
        }
  }

  private void createSchemaFromRequestBody(Operation operation, Components components) {
    if (operation.getRequestBody() == null) {
        return;
    }

    if (operation.getRequestBody().get$ref() != null) {
        this.createSchemaFromRefRequestBody(operation, components);

        return;
    }

    this.createSchemaFromFormData(operation, components);
  }

  private void createSchemaFromRefRequestBody(Operation operation, Components components) {
    Map<String, Schema> schemas = components.getSchemas();
    Map<String, RequestBody> requestBodies = components.getRequestBodies();
    RequestBody requestBody = operation.getRequestBody();

    String shortRef = ModelUtils.getSimpleRef(requestBody.get$ref());
    String newShortRef = operation.getOperationId() + "Body";

    // change the name in schemas
    Schema schema = schemas.get(shortRef);
    schemas.remove(shortRef);
    components.addSchemas(newShortRef, schema);

    // change the name in the requestBodies
    RequestBody componentRequestBody = requestBodies.get(shortRef);
    requestBodies.remove(shortRef);
    components.addRequestBodies(newShortRef, componentRequestBody);

    ModelUtils.getSchemaFromRequestBody(componentRequestBody).set$ref(newShortRef);
    requestBody.set$ref(newShortRef);
  }

  private void createSchemaFromFormData(Operation operation, Components components) {
    RequestBody requestBody = operation.getRequestBody();
    Schema schema = ModelUtils.getSchemaFromRequestBody(requestBody);
    String ref = operation.getOperationId() + "Body";

    // add new schema
    components.addSchemas(ref, schema);
    components.addRequestBodies(ref, requestBody);
    requestBody.set$ref(ref);
    requestBody.setDescription(org.openapitools.codegen.utils.StringUtils.camelize(ref));
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
