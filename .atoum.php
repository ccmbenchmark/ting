<?php

use \atoum\atoum;

$report = $script->addDefaultReport();
$coverageField = new atoum\report\fields\runner\coverage\html('Ting', __DIR__ . '/tests/coverage/');
$coverageField->setRootUrl('file://' . __DIR__ . '/tests/coverage/index.html');
$report->addField($coverageField);
$runner->addTestsFromDirectory('tests/units');
