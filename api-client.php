<?php
error_reporting(E_ERROR | E_PARSE);
class vistapanelApi
{
    
    public $cpanel_url = "";
    public $loggedin = false;
    public $vistapanel_session = "";
    public $vistapanel_sessionName = "PHPSESSID";
    public $vistapanel_token = 0;
    public $accountUsername = "";
    
    function getLineWithString($content, $str)
    {
        $lines = explode("\n", $content);
        foreach ($lines as $lineNumber => $line) {
            if (strpos($line, $str) !== false) {
                return $line;
            }
        }
        return -1;
    }
    function SimpleCurl($url = "", $post = false, $postfields = array(), $header = false, $httpheader = array(), $followlocation = false)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($post == true) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
        }
        if ($header == true) {
            curl_setopt($ch, CURLOPT_HEADER, true);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
        if ($followlocation == true) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        }
        return curl_exec($ch);
        curl_close($ch);
    }
    function classError($error)
    {
        die("vistapanelApi_error: " . $error);
    }
    
    function CheckCpanelUrl()
    {
        if (empty($this->cpanel_url)) {
            $this->classError("Please set cpanel_url first.");
        }
        if (substr($this->cpanel_url, -1) == "/") {
            $this->cpanel_url = substr_replace($this->cpanel_url, "", -1);
        }
    }
    
    function CheckLogin()
    {
        if ($this->loggedin == false) {
            $this->classError("Not logged in.");
        }
    }
    
    function Login($username, $password, $theme = "PaperLantern")
    {
        $this->CheckCpanelUrl();
        if (!isset($username)) {
            $this->classError("username is required.");
        }
        if (!isset($password)) {
            $this->classError("password is required.");
        }
        $login = $this->SimpleCurl($this->cpanel_url . "/login.php", true, array(
            "uname" => $username,
            "passwd" => $password,
            "theme" => $theme,
            "seeesurf" => "567811917014474432"
        ), true, array(), true);
        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $login, $matches);
        $cookies = array();
        foreach ($matches[1] as $item) {
            parse_str($item, $cookie);
            $cookies = array_merge($cookies, $cookie);
        }
        if (!empty($cookies['PHPSESSID'])) {
            if (strpos($login, "document.location.href = 'panel/indexpl.php") !== false) {
                if ($this->loggedin !== true) {
                    $this->loggedin           = true;
                    $this->accountUsername    = $username;
                    $this->vistapanel_session = $cookies[$this->vistapanel_sessionName];
                    $this->vistapanel_token   = $this->getToken();
                    $checkImportantNotice     = $this->SimpleCurl($this->cpanel_url . "/panel/indexpl.php", false, array(), false, array(
                        "Cookie: " . $this->vistapanel_sessionName . "=" . $this->vistapanel_session
                    ));
                    if (strpos($checkImportantNotice, "To notify you of changes to service and offers we need permission to send you email") !== false) {
                        $this->SimpleCurl($this->cpanel_url . "/panel/approve.php", true, array(
                            "submit" => true
                        ), false, array(
                            "Cookie: " . $this->vistapanel_sessionName . "=" . $this->vistapanel_session
                        ));
                    }
                } else {
                    $this->classError("You are already logged in.");
                }
            } else {
                $this->classError("Invalid login credentials.");
            }
        } else {
            $this->classError("Unable to login.");
        }
    }
    
    function createDatabase($dbname = "")
    {
        $this->CheckCpanelUrl();
        $this->CheckLogin();
        if (empty($dbname)) {
            $this->classError("dbname is required.");
        }
        $this->SimpleCurl($this->cpanel_url . "/panel/indexpl.php?option=mysql&cmd=create", true, array(
            "db" => $dbname
        ), false, array(
            "Cookie: " . $this->vistapanel_sessionName . "=" . $this->vistapanel_session
        ));
        return true;
    }
    function changePackage($username, $newPackage)
{
    // Check for required parameters
    if (empty($username)) {
        throw new Exception("Username is required.");
    }
    if (empty($newPackage)) {
        throw new Exception("New package name is required.");
    }

    // Construct API request
    $url = "https://panel.myownfreehost.net/xml-api/changepackage.php";
    $data = array(
        "user" => $username,
        "pkg" => $newPackage
    );
    $options = array(
        CURLOPT_USERPWD => "your_username:your_password", // Replace with your actual username and password
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_RETURNTRANSFER => true
    );

    // Send API request and parse response
    $curl = curl_init($url);
    curl_setopt_array($curl, $options);
    $response = curl_exec($curl);
    curl_close($curl);

    $xml = simplexml_load_string($response);
    if ($xml->result->status == 1) {
        return true;
    } else {
        throw new Exception("Package change failed: " . $xml->result->statusmsg);
    }
}

    function uploadCert($domainname,$key,$csr)
    {
        $this->CheckCpanelUrl();
        $this->CheckLogin();
        if (empty($domainname)) {
            $this->classError("domainname is required."),
            
        }
        if (empty($key)) {
            $this->classError("key is required."),
            
        }
        $this->SimpleCurl($this->cpanel_url . "/panel/modules-new/sslconfigure/uploadkey.php", true, array(
            "domain_name" => $domainname,
            "csr" => $csr,
            "key" => $key
            
        ), false, array(
            "Cookie: " . $this->vistapanel_sessionName . "=" . $this->vistapanel_session
        ));
        return true;
    }
    
    function listDatabases()
    {
        $this->CheckCpanelUrl();
        $this->CheckLogin();
        $databases   = array();
        $htmlContent = $this->SimpleCurl($this->cpanel_url . "/panel/indexpl.php?option=pma", false, array(), false, array(
            "Cookie: " . $this->vistapanel_sessionName . "=" . $this->vistapanel_session
        ));
        $DOM         = new DOMDocument();
        libxml_use_internal_errors(true);
        $DOM->loadHTML($htmlContent);
        libxml_clear_errors();
        $Header = $DOM->getElementsByTagName('th');
        $Detail = $DOM->getElementsByTagName('td');
        foreach ($Header as $NodeHeader) {
            $aDataTableHeaderHTML[] = trim($NodeHeader->textContent);
        }
        $i = 0;
        $j = 0;
        foreach ($Detail as $sNodeDetail) {
            $aDataTableDetailHTML[$j][] = trim($sNodeDetail->textContent);
            $i                          = $i + 1;
            $j                          = $i % count($aDataTableHeaderHTML) == 0 ? $j + 1 : $j;
        }
        for ($i = 0; $i < count($aDataTableDetailHTML); $i++) {
            for ($j = 0; $j < count($aDataTableHeaderHTML); $j++) {
                $aTempData[$i][$aDataTableHeaderHTML[$j]] = $aDataTableDetailHTML[$i][$j];
            }
        }
        $aDataTableDetailHTML = $aTempData;
        unset($aTempData);
        foreach ($aDataTableDetailHTML as $database) {
            $databases[str_replace($this->accountUsername . "_", "", array_shift($database))] = true;
        }
        return $databases;
    }
    
    function deleteDatabase($database = "")
    {
        $this->CheckCpanelUrl();
        $this->CheckLogin();
        if (empty($database)) {
            $this->classError("database is required.");
        }
        if (!array_key_exists($database, $this->listDatabases())) {
            $this->classError("The database you're trying to remove doesn't exists.");
        }
        $this->SimpleCurl($this->cpanel_url . "/panel/indexpl.php?option=mysql&cmd=remove", true, array(
            "toremove" => $this->accountUsername . "_" . $database,
            "Submit2" => "Remove Database"
        ), false, array(
            "Cookie: " . $this->vistapanel_sessionName . "=" . $this->vistapanel_session
        ));
        return true;
    }
    
    function getPhpmyadminLink($database = "")
    {
        $this->CheckCpanelUrl();
        $this->CheckLogin();
        if (empty($database)) {
            $this->classError("database is required.");
        }
        if (!array_key_exists($database, $this->listDatabases())) {
            $this->classError("The database you're trying to get the PMA link of doesn't exists.");
        }
        $htmlContent = $this->SimpleCurl($this->cpanel_url . "/panel/indexpl.php?option=pma", false, array(), false, array(
            "Cookie: " . $this->vistapanel_sessionName . "=" . $this->vistapanel_session
        ));
        $dom         = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML($htmlContent);
        libxml_clear_errors();
        $links = $dom->getElementsByTagName('a');
        foreach ($links as $link) {
            if (strpos($link->getAttribute('href'), "&db=" . $this->accountUsername . "_" . $database) !== false) {
                return $link->getAttribute('href');
            }
        }
    }
    
    function getToken()
    {
        $this->CheckCpanelUrl();
        $this->CheckLogin();
        $homepage = $this->SimpleCurl($this->cpanel_url . "/panel/indexpl.php", false, array(), false, array(
            "Cookie: " . $this->vistapanel_sessionName . "=" . $this->vistapanel_session
        ));
        $json     = $this->getLineWithString($homepage, "/panel/indexpl.php?option=passwordchange&ttt=");
        $json     = substr_replace($json, "", -1);
        $json     = json_decode($json, true);
        $url      = $json['url'];
        return (int) filter_var($url, FILTER_SANITIZE_NUMBER_INT);
    }
    
    function listAddonDomains()
    {
        $this->CheckCpanelUrl();
        $this->CheckLogin();
        $addonDomains = array();
        $htmlContent  = $this->SimpleCurl($this->cpanel_url . "/panel/indexpl.php?option=domains&ttt=" . $this->vistapanel_token, false, array(), false, array(
            "Cookie: " . $this->vistapanel_sessionName . "=" . $this->vistapanel_session
        ));
        $DOM          = new DOMDocument();
        libxml_use_internal_errors(true);
        $DOM->loadHTML($htmlContent);
        libxml_clear_errors();
        $Header = $DOM->getElementsByTagName('th');
        $Detail = $DOM->getElementsByTagName('td');
        foreach ($Header as $NodeHeader) {
            $aDataTableHeaderHTML[] = trim($NodeHeader->textContent);
        }
        $i = 0;
        $j = 0;
        foreach ($Detail as $sNodeDetail) {
            $aDataTableDetailHTML[$j][] = trim($sNodeDetail->textContent);
            $i                          = $i + 1;
            $j                          = $i % count($aDataTableHeaderHTML) == 0 ? $j + 1 : $j;
        }
        for ($i = 0; $i < count($aDataTableDetailHTML); $i++) {
            for ($j = 0; $j < count($aDataTableHeaderHTML); $j++) {
                $aTempData[$i][$aDataTableHeaderHTML[$j]] = $aDataTableDetailHTML[$i][$j];
            }
        }
        $aDataTableDetailHTML = $aTempData;
        unset($aTempData);
        foreach ($aDataTableDetailHTML as $addonDomain) {
            $addonDomains[array_shift($addonDomain)] = true;
        }
        return $addonDomains;
    }
    
    function listSubDomains()
    {
        $this->CheckCpanelUrl();
        $this->CheckLogin();
        $subDomains  = array();
        $htmlContent = $this->SimpleCurl($this->cpanel_url . "/panel/indexpl.php?option=subdomains&ttt=" . $this->vistapanel_token, false, array(), false, array(
            "Cookie: " . $this->vistapanel_sessionName . "=" . $this->vistapanel_session
        ));
        $DOM         = new DOMDocument();
        libxml_use_internal_errors(true);
        $DOM->loadHTML($htmlContent);
        libxml_clear_errors();
        $Header = $DOM->getElementsByTagName('th');
        $Detail = $DOM->getElementsByTagName('td');
        foreach ($Header as $NodeHeader) {
            $aDataTableHeaderHTML[] = trim($NodeHeader->textContent);
        }
        $i = 0;
        $j = 0;
        foreach ($Detail as $sNodeDetail) {
            $aDataTableDetailHTML[$j][] = trim($sNodeDetail->textContent);
            $i                          = $i + 1;
            $j                          = $i % count($aDataTableHeaderHTML) == 0 ? $j + 1 : $j;
        }
        for ($i = 0; $i < count($aDataTableDetailHTML); $i++) {
            for ($j = 0; $j < count($aDataTableHeaderHTML); $j++) {
                $aTempData[$i][$aDataTableHeaderHTML[$j]] = $aDataTableDetailHTML[$i][$j];
            }
        }
        $aDataTableDetailHTML = $aTempData;
        unset($aTempData);
        foreach ($aDataTableDetailHTML as $subDomain) {
            $subDomains[array_shift($subDomain)] = true;
        }
        unset($subDomains[current(array_keys($subDomains))]);
        return $subDomains;
    }
    

    function GetSoftaculousLink()
    {
        $this->CheckCpanelUrl();
        $this->CheckLogin();
        $getlink = $this->SimpleCurl($this->cpanel_url . "/panel/indexpl.php?option=installer&ttt=" . $this->vistapanel_token, false, array(), true, array(
            "Cookie: " . $this->vistapanel_sessionName . "=" . $this->vistapanel_session
        ), true);
        if (preg_match('~Location: (.*)~i', $getlink, $match)) {
            $location = trim($match[1]);
        }
        return $location;
    }
    
 
    function Logout()
    {
        $this->CheckCpanelUrl();
        $this->CheckLogin();
        $this->SimpleCurl($this->cpanel_url . "/panel/indexpl.php?option=signout", false, array(), false, array(
            "Cookie: " . $this->vistapanel_sessionName . "=" . $this->vistapanel_session
        ), true);
        $cpanel_url         = "";
        $loggedin           = false;
        $vistapanel_session = "";
        $vistapanel_token   = 0;
        $accountUsername    = "";
        return true;
    }
}
?>
