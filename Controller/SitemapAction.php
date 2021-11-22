<?php

namespace Ahmad\SitemapMigrate\Controller;

use Snowdog\DevTest\Model\UserManager;
use Ahmad\SitemapMigrate\Model\SitemapManager;

class SitemapAction
{
    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var SitemapManager
     */
    private $sitemapManager;

    public function __construct(
        UserManager $userManager,
        SitemapManager $sitemapManager
    ) {
        $this->userManager = $userManager;
        $this->sitemapManager = $sitemapManager;
    }

    public function execute()
    {
        if (isset($_SESSION['login'])) {
            $user = $this->userManager->getByLogin($_SESSION['login']);
            $currentDirectory = getcwd();
            $uploadDirectory = "\\uploads\\";

            $errors = []; // Store errors here

            $fileExtensionsAllowed = ['csv']; // These will be the only file extensions allowed 

            $fileName = $_FILES['the_file']['name'];
            $fileSize = $_FILES['the_file']['size'];
            $fileTmpName  = $_FILES['the_file']['tmp_name'];
            $fileType = $_FILES['the_file']['type'];

            $fileArr = explode('.',$fileName);
            $keys = array_keys($fileArr);
            $fileArr[$keys[sizeof($keys) - 1]];
    
            $fileExtension = strtolower($fileArr[$keys[sizeof($keys) - 1]]);

            if (!file_exists($currentDirectory.$uploadDirectory)) {
                mkdir($currentDirectory.$uploadDirectory, 0777, true);
            }

            $uploadPath = $currentDirectory . $uploadDirectory . basename($fileName); 

            if (isset($_POST['submit'])) {

                if (! in_array($fileExtension,$fileExtensionsAllowed)) {
                    $errors[] = "This file extension is not allowed. Please upload a JPEG or PNG file";
                }

                if ($fileSize > 40000000) {
                    $errors[] = "File exceeds maximum size (4MB)";
                }

                if (empty($errors)) {
                    $didUpload = move_uploaded_file($fileTmpName, $uploadPath);
                    if($this->readCsv($uploadPath, $user)){
                        $_SESSION['flash'] = 'Sitemap data syc successfully!';
                    }
                    if ($didUpload) {
                        echo "The file " . basename($fileName) . " has been uploaded";
                    } else {
                        echo "An error occurred. Please contact the administrator.";
                    }
                } else {
                    foreach ($errors as $error) {
                    echo $error . "These are the errors" . "\n";
                    }
                }

            }
            header('Location: /sitemap');
            return;
        }
        header('Location: /');
        return;
    }

    public function readCsv($path, $user)
    {
        $i = 1;
        $file = fopen($path, 'r');
        while (($line = fgetcsv($file)) !== FALSE) {
            if( $i == 1 ) {
                $i++;
                continue;
            }
            if( !empty($line[0]) ) {
                $host = $line[0];
                $hostname = str_replace("https://","",$line[0]);
                $hostname = str_replace("http://","",$hostname);
                $hostname = explode('/', $hostname, 2);

                if($website = $this->sitemapManager->getByHostname($hostname[0])){
                    if( !empty($hostname[1]) && empty($this->sitemapManager->check($website, $hostname[1])) ){
                        $this->sitemapManager->createPage($website, $hostname[1]);
                    }
                }else{
                    if ( $webId = $this->sitemapManager->create($user, $host, $hostname[0]) ){
                        if(!empty($hostname[1])){
                            $website = $this->sitemapManager->getById($webId);
                            $this->sitemapManager->createPage($website, $host);
                        }
                    }
                }
                
            }
             
        }
        fclose($file);
        return true;
    }
}