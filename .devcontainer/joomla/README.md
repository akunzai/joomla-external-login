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

- Title: `Default`
- Auto-register: `Yes`
- Auto-update: `Yes`

### CAS parameters

- URL: `auth.dev.local`
- Path: `realms/demo/protocol/cas`
- Use CAS 3.0 URL: `Yes`

### Attributes

- Username xpath: `string(cas:attributes/cas:email)`
- Full name xpath: `string(cas:attributes/cas:display_name)`
- Email xpath: `string(cas:attributes/cas:email)`

## Edit Module

> Content->`Site Modules`->`External login`

- Servers: `Default`
- Position: `sidebar-right`
- Menu Assignment: `On all pages`
- Layout: `Default`
- Show logout: `Yes`
- Status: `Published`
