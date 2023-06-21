### Methods

#### `setCpanelUrl($url)`

Sets the URL of the VistaPanel control panel.

- `$url` (string): The URL of the VistaPanel control panel. (ex: panel.xxx.tld)

#### `login($username, $password, $theme)`

Logs in to the VistaPanel control panel.

- `$username` (string): The username of the account.
- `$password` (string): The password of the account.
- `$theme` (string): The theme to use after logging in (default: "PaperLantern").

#### `createDatabase($dbname)`

Creates a new database.

- `$dbname` (string): The name of the database, without the account prefix.

#### `listDatabases()`

Returns an array of databases associated with the logged-in account.

#### `deleteDatabase($database)`

Deletes a database.

- `$database` (string): The name of the database, without the account prefix.

#### `getPhpmyadminLink($database)`

Returns the phpMyAdmin link for a specific database.

- `$database` (string): The name of the database, without the account prefix.

#### `listDomains($option)`

Returns an array of domains in a specific category.

- `$option` (string): The category of domains to retrieve. Available options: "all", "addon", "sub", and "parked" (default: "all").

#### `createRedirect($domainname, $target)`

Creates a redirect for a domain.

- `$domainname` (string): The name of the domain.
- `$target` (string): The target URL for the redirect.

#### `deleteRedirect($domainname)`

Deletes a redirect for a domain.

- `$domainname` (string): The name of the domain.

#### `uploadKey($domainname, $key, $csr)`

Uploads an SSL key for a domain.

- `$domainname` (string): The name of the domain.
- `$key` (string): The content of the SSL key file.
- `$csr` (string): The content of the Certificate Signing Request (CSR) file.

#### `uploadCert($domainname, $cert)`

Uploads an SSL certificate for a domain.

- `$domainname` (string): The name of the domain.
- `$cert` (string): The content of the SSL certificate file.

#### `getSSLPrivateKey($domain)`

Get the currently installed SSL Key for a domain

- `$domain` (string): The name of the domain.

#### `getSSLCertificate($domain)`

Get the currently installed SSL Certificate for a domain

- `$domain` (string): The name of the domain.

#### `getSoftaculousLink()`

Returns the Softaculous link for the control panel.

#### `logout()`

Logs out from the control panel, and resets client configuration.

#### `approveNotification()`

Allows iFastNet to send you notifications about account suspensions, also unlocks the control panel.
