### Example Usage

```php
<?php
require_once 'VistapanelApi.php';

$vpClient = new VistapanelApi();
$vpClient->setCpanelUrl('https://example.com');
$vpClient->login('username', 'password');

$databases = $vpClient->listDatabases();
foreach ($databases as $database => $value) {
    echo $database . "\n";
}

$vpClient->logout();
?>
```