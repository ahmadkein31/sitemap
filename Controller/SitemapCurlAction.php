<?php

namespace Ahmad\SitemapMigrate\Controller;

use Snowdog\DevTest\Model\UserManager;
use Ahmad\SitemapMigrate\Model\SitemapManager;

class SitemapCurlAction
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
            
            $errors = [];
            $data = [];

            if (empty($_POST['sitemapurl'])) {
                $errors['curl'] = 'Curl is required.';
            }else{
                $this->curlSitemap($_POST['sitemapurl'], $user);
            }

            if (!empty($errors)) { 
                $data['success'] = false;
                $data['errors'] = $errors;
            } else { 
                $data['success'] = true;
                $data['message'] = 'Success!';
            }

            echo json_encode($data);
        }
    }

    protected function curlSitemap($url, $user)
    {
        $data = file_get_contents($url);
        $xml = simplexml_load_string($data, "SimpleXMLElement", LIBXML_NOCDATA);
        $json = json_encode($xml);
        $array = json_decode($json,TRUE);
        $url = $array['url'];
        return $this->uploadData($url, $user);
    }

    protected function uploadData($data, $user)
    {
        $i = 1;
        foreach ($data as $url_list) {
            if( !empty($url_list['loc']) ) { 
                $host = $url_list['loc'];
                $hostname = str_replace("https://","",$url_list['loc']);
                $hostname = str_replace("http://","",$hostname);
                $hostname = explode('/', $hostname, 2);

                if($website = $this->sitemapManager->getByHostname($hostname[0])){ 
                    if( !empty($hostname[1]) && empty($this->sitemapManager->check($website, $hostname[1])) ){
                    $website = $this->sitemapManager->getByHostname($hostname[0]);
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
            $i++;
        }
        return true;
    }
}