# Joomla! Setup

## Extension Installation

> System->Install->Extensions

Upload Package File: `pkg_externallogin.zip`

## Enable Extensions

> System->Manage->Extensions

- Plugin: `Authentication - External Login`
- Plugin: `System - External Login`
- Plugin: `System - CAS Login`

## Add CAS Server definition

> Components->`External Login`->Servers->New->CAS

### Server details

- Title: `Keycloak`
- Auto-register: `Yes`
- Auto-update: `Yes`

### CAS parameters

- Use SSL: `No`
- URL: `auth.dev.local`
- Path: `realms/demo/protocol/cas`
- Use CAS 3.0 URL: `Yes`
- Port: `8080`

### Attributes

- Username xpath: `string(cas:attributes/cas:email)`
- Full name xpath: `string(cas:attributes/cas:display_name)`
- Email xpath: `string(cas:attributes/cas:email)`

## Edit Module

> Content->`Site Modules`->`External login`

### Module

- Servers: `Keycloak`
- Position: `sidebar-right`
- Status: `Published`

### Menu Assignment

- Menu Assignmnet: `On all pages`

### Advanced

- Layout: `default`
- Show logout: `Yes`
