<?php

namespace DH\ConfigBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ConfigTestController extends WebTestCase
{
    protected $client;
    protected $verbose;
    
    public function __construct()
    {
        $this->client = static::createClient();
        $this->verbose = true;
    }
    
    public function testIndex()
    {
        //dashboard
        if ($this->verbose) {ob_flush(); echo PHP_EOL.'start dashboard test, going to list page'.PHP_EOL;}
        $crawler = $this->client->request('GET', '/admin/dashboard');
        $this->assertTrue($crawler->filter('html:contains("Settings")')->count() > 0, 'string "Settings" not found on dashboad page');
        $this->assertTrue($crawler->filter('html:contains("Add new")')->count() > 0, 'string "Add new" not found on dashboad page');
        $this->assertTrue($crawler->filter('html:contains("List")')->count() > 0, 'string "List" not found on dashboad page');
        $linkList = $crawler->filter('a:contains("List")')->eq(0)->link();
        $crawler = $this->client->click($linkList);
        //list
        $this->assertTrue($crawler->filter('html:contains("Default Value")')->count() > 0, 'string "Default Value" not found on list page');
        $this->assertTrue($crawler->filter('html:contains("Current Value")')->count() > 0, 'string "Current Value" not found on list page');
        $this->assertTrue($crawler->filter('html:contains("Description")')->count() > 0, 'string "Description" not found on list page');
        $this->assertTrue($crawler->filter('html:contains("Type")')->count() > 0, 'string "Type" not found on list page');
        $this->assertTrue($crawler->filter('html:contains("Min")')->count() > 0, 'string "Min" not found on list page');
        $this->assertTrue($crawler->filter('html:contains("Max")')->count() > 0, 'string "Max" not found on list page');
        $this->assertTrue($crawler->filter('html:contains("Type")')->count() > 0, 'string "Type" not found on list page');
        $this->assertTrue($crawler->filter('html:contains("Choices")')->count() > 0, 'string "Choices" not found on list page');
        $this->assertTrue($crawler->filter('html:contains("Section")')->count() > 0, 'string "Section" not found on list page');
        $this->assertTrue($crawler->filter('html:contains("Add new")')->count() > 0, 'string "Add new" not found on list page');        
        //data types
        $this->dataTypeTest('integer', 'phpunit integer test1', 'integer added by phpunit, test1', 10, 20, 1, 99);
        $this->dataTypeTest('integer', 'phpunit integer test2', 'integer added by phpunit, test2', 10, 20, -1, 9);//ristrictions violated
        $this->dataTypeTest('choice', 'phpunit choices test1', 'choices added by phpunit, test1', 'a', 'b', '', '', 'a,b,c,d,e');
        $this->dataTypeTest('multiplechoice', 'phpunit multiplechoices test1', 'multiplechoices added by phpunit, test1', 'a,b', 'b,c,d', '', '', 'a,b,c,d,e');
    }
    
    public function dataTypeTest($dataType, $name, $description, $defaultValue, $currentValue, $min = '', $max = '', $choices = '')
    {
        $restrictionViolation = ($min != '' AND ($currentValue>$min OR $defaultValue>$min)) OR ($max != '' AND ($currentValue<$max OR $defaultValue<$max));
        if ($this->verbose) echo 'start '.$dataType.' data type test, going to list page'.PHP_EOL;
        $crawler = $this->goToListPage();
        $crawler = $this->deleteSettingByName($crawler, $name);
        //create
        if ($this->verbose) echo 'starting creation of '.$dataType.' setting'.PHP_EOL;
        $crawler = $this->createSetting($crawler, $dataType, $name, $description, $defaultValue, $currentValue, $min, $max, $choices, $restrictionViolation);
        //back to list
        if (!$restrictionViolation)
        {
            if ($this->verbose) echo 'setting was created, going back to list page'.PHP_EOL;
            $linkList = $crawler->filter('a:contains("Return to list")')->eq(0)->link();
            $crawler = $this->client->click($linkList);
            //check list page
            if ($this->verbose) echo 'checking if new setting appears in list page'.PHP_EOL;
            $this->assertTrue($crawler->filter('a:contains("'.$name.'")')->count() > 0, 'name of newly created setting '.$name.' not found on list page');
            $linkEdit = $crawler->filter('a:contains("'.$name.'")')->eq(0)->link();
            $uri = $linkEdit->getUri();
            $id = ltrim(strrchr(strstr($uri, '/edit', true), '/'), '/');//get ID of newly created setting
            $select = $crawler->filterXPath('//select[@name="action"]');
            $checkbox = $crawler->filterXPath('//input[@type="checkbox" and @name="idx[]" and @value="'.$id.'"]');
            $this->assertTrue($checkbox->count() > 0);
            //go to edit page
            if ($this->verbose) echo 'going to edit page'.PHP_EOL;
            $crawler = $this->client->click($linkEdit);
            //check edit page
            if ($this->verbose) echo 'checking edit page'.PHP_EOL;
            $this->checkEditpage($crawler, $dataType, $name, $description, $defaultValue, $currentValue, $min, $max, $choices);
            //back to list
            if ($this->verbose) echo 'going back to list page'.PHP_EOL;
            $linkList = $crawler->filter('a:contains("Return to list")')->eq(0)->link();
            $crawler = $this->client->click($linkList);      
            // prepare delete
            if ($this->verbose) echo 'preparing deletion of new setting'.PHP_EOL;
            $crawler = $this->deleteSettingByName($crawler, $name);
            $this->assertTrue($crawler->filter('a:contains("'.$name.'")')->count() == 0, 'setting '.$name.', ID: '.$id.' could not be deleted');
        }
    }
    
    public function goToListPage()
    {    
        $crawler = $this->client->request('GET', '/admin/dashboard');
        $linkList = $crawler->filter('a:contains("List")')->eq(0)->link();
        $crawler = $this->client->click($linkList);
        $linkList = $crawler->filter('a:contains("Add new")')->eq(0)->link();
        return $this->client->click($linkList);
    }

    public function checkEditPage($crawler, $dataType, $name, $description, $defaultValue, $currentValue, $min, $max, $choices)
    {    
        $this->assertTrue($crawler->filter('html:contains("Defaultvalue")')->count() > 0, 'string "Defaultvalue" not found on edit page');
        $this->assertTrue($crawler->filter('html:contains("Currentvalue")')->count() > 0, 'string "Currentvalue" not found on edit page');
        $this->assertTrue($crawler->filter('html:contains("Description")')->count() > 0, 'string "Description" not found on edit page');
        $this->assertTrue($crawler->filter('html:contains("Type")')->count() > 0, 'string "Type" not found on edit page');
        $this->assertTrue($crawler->filter('html:contains("Section")')->count() > 0, 'string "Section" not found on edit page');
        $this->assertTrue($crawler->filter('html:contains("Updated")')->count() > 0, 'string "Updated" not found on edit page');
        $this->assertTrue($crawler->filterXPath('//input[@value="'.$name.'"]')->count() > 0, 'name string "'.$name.'" not found on edit page');
        $this->assertTrue($crawler->filterXPath('//input[@value="'.$description.'"]')->count() > 0, 'description string "'.$name.'" not found on edit page');
        switch ($dataType)
        {
            case 'integer':
            case 'float':
            case 'time':
            case 'datetime':
                $this->assertTrue($crawler->filterXPath('//input[@value="'.$defaultValue.'"]')->count() > 0, 'default value "'.$defaultValue.'" not found on edit page');
                $this->assertTrue($crawler->filterXPath('//input[@value="'.$currentValue.'"]')->count() > 0, 'current value "'.$currentValue.'" not found on edit page');
                $this->assertTrue($crawler->filter('html:contains("Min")')->count() > 0, 'string "Min" not found on edit page');
                $this->assertTrue($crawler->filter('html:contains("Max")')->count() > 0, 'string "Max" not found on edit page');
                $this->assertFalse($crawler->filter('html:contains("Choices")')->count() > 0, 'string "Choices" found on edit page');
                break;
            case 'choice':
            case 'multiplechoice':
                $defaultValues = explode(',', $defaultValue);
                $currentValues = explode(',', $currentValue);
                foreach ($defaultValues AS $defValue) $this->assertTrue($crawler->filterXPath('//option[@value="'.$defValue.'" and @selected="selected"]')->count() > 0, 'default value "'.$defValue.'" not selected on edit page');
                foreach ($currentValues AS $curValue) $this->assertTrue($crawler->filterXPath('//option[@value="'.$curValue.'" and @selected="selected"]')->count() > 0, 'current value "'.$curValue.'" not selected on edit page');
                //$this->assertTrue($crawler->filterXPath('//option[@value="'.$choices.'"]')->count() > 0, 'choices "'.$choices.'" not found on edit page');
                $this->assertTrue($crawler->filter('html:contains("Choices")')->count() > 0, 'string "Choices" not found on edit page');
                $this->assertFalse($crawler->filter('html:contains("Min")')->count() > 0, 'string "Min" found on edit page');
                $this->assertFalse($crawler->filter('html:contains("Max")')->count() > 0, 'string "Max" found on edit page');
                break;
        }
        $form = $crawler->selectButton('Update')->form();
        $uri = $form->getUri();
        $token = substr($uri, strpos($uri, 'uniqid=')+7);//get form token
        foreach ($form AS $field) print_r($field);
    }

    public function createSetting($crawler, $dataType, $name, $description, $defaultValue, $currentValue, $min = '', $max = '', $choices = '', $restrictionViolation)
    {    
        $form = $crawler->selectButton('Create')->form();
        $uri = $form->getUri();
        $token = substr($uri, strpos($uri, 'uniqid=')+7);//get form token
        $form[$token.'[type]']->select($dataType);
        $form[$token.'[name]'] = $name;
        $form[$token.'[description]'] = $description;
        $form[$token.'[defaultValue]'] = $defaultValue;
        $form[$token.'[currentValue]'] = $currentValue;
        if ($min != '') $form[$token.'[min]'] = $min;
        if ($max != '') $form[$token.'[max]'] = $max;
        if ($choices != '') $form[$token.'[choices]'] = $choices;
        $crawlerNew = $this->client->submit($form);
        if (!$restrictionViolation)
        {
            $this->assertTrue($this->client->getResponse()->isRedirect(), 'if no redirection takes place, most probably setting '.$name.' already exists or a restriction violation took place.');
            return $this->client->followRedirect();
        }
        else
        {
            if ($this->verbose) echo 'creation failed'.PHP_EOL;
            return $crawler;
        }
    }

    public function deleteSettingById($crawler, $id)
    {    
        $cbs = $crawler->filterXPath('//input[@type="checkbox" and @name="idx[]"]');
        $counter = 0;
        foreach ($cbs AS $cb)
        {
            if ($cb->getAttribute('value') == $id) {$index = $counter; break;}            
            $counter++;
        }
        $this->assertTrue(isset($index), 'setting with id='.$id.' not found');
        $form = $crawler->selectButton('OK')->form();        
        $form['idx['.$index.']']->tick();
        $crawler = $this->client->submit($form);
        if ($this->verbose) echo 'deletion submitted'.PHP_EOL;
        $form = $crawler->selectButton('Yes, execute')->form();
        $crawler = $this->client->submit($form);
        if ($this->verbose) echo 'deletion confirmed'.PHP_EOL;
        return $crawler;
    }

    public function deleteSettingByName($crawler, $name)
    {    
        $input = $crawler->filterXPath('//a[text()="'.$name.'"]/../../td[1]/input');
        if ($input->count()>0) $id = $input->attr('value'); else return $crawler;
        return $this->deleteSettingById($crawler, $id);
    }

    public function showDOM($crawler)
    {    
        //foreach ($crawler as $domElement) print $domElement->nodeName;
        $html = '';
        foreach ($crawler as $domElement) $html.= $domElement->ownerDocument->saveHTML();
        return $html;
        /*$doc = new \DOMDocument('1.0');
        foreach ($crawler as $domElement ) {$clone = $doc->importNode($domElement->cloneNode(true), true); $doc->appendChild($clone);}
        return $doc->saveHTML();*/
    }
}
