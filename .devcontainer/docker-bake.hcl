// Local parallel multi-target builds:
//   docker buildx bake -f docker-bake.hcl --load
//
// CI uses docker/build-push-action with type=gha (see .github/workflows/e2e.yml).
// Raw bake/buildx CLI type=gha does not export cache in GHA for this workflow.

variable "JOOMLA_VERSION" {
  default = "6.1.3"
}

variable "PHP_VERSION" {
  default = "8.4"
}

group "default" {
  targets = ["joomla", "keycloak"]
}

target "joomla" {
  context    = "./joomla"
  dockerfile = "Dockerfile"
  tags       = ["joomla:external-login"]
  args = {
    JOOMLA_VERSION = JOOMLA_VERSION
    PHP_VERSION    = PHP_VERSION
  }
}

target "keycloak" {
  context    = "./keycloak"
  dockerfile = "Dockerfile"
  tags       = ["keycloak:cas"]
}
