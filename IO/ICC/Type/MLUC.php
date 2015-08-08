<?php

require_once dirname(__FILE__).'/../Bit.php';
require_once dirname(__FILE__).'/Base.php';

class IO_ICC_Type_MLUC extends IO_ICC_Type_Base {
    const DESCRIPTION = 'MultiLocalazed Unicode';
    var $strings = null;
    function parseContent($type, $content, $opts = array()) {
        $reader = new IO_ICC_Bit();
    	$reader->input($content);
        $this->type = $type;
        $reader->incrementOffset(8, 0); // skip head 8 bytes
        $recordNum = $reader->getUI32BE();
        $recordSize = $reader->getUI32BE();
        $records = array();
        for ($i = 0 ; $i < $recordNum ; $i++) {
            $record = array();
            $langCode = $reader->getData(2); // UI16BE on spec
            $countryCode = $reader->getData(2); // UI16BE on spec
            $size = $reader->getUI32BE();
            $offset = $reader->getUI32BE();
            $records []=
                array(
                      'LangCode' => $langCode,
                      'CountryCode' => $countryCode,
                      '_size' => $size,
                      '_offset' => $offset,
                      );
        }
        foreach ($records as &$record) {
            $reader->setOffset($record['_offset'], 0);
            $record['String'] = $reader->getData($record['_size']);
        }
        $this->records = $records;
    }

    function dumpContent($type, $opts = array()) {
        foreach ($this->records as $record) {
            $langCode = $record['LangCode'];
            $countryCode = $record['CountryCode'];
            $string = $record['String'];
            echo "\tCode:{$langCode}_$countryCode String:$string".PHP_EOL;
        }
    }

    function buildContent($type, $opts = array()) {
        $writer = new IO_Bit();
        $writer->putData($this->type);
        $writer->putData("\0\0\0\0");
        //
        $writer->putUI32BE(count($this->records));
        $writer->putUI32BE(12);
        list($recordOffset, $dummy) = $writer->getOffset();
        foreach ($this->records as $record) {
            $writer->putData($record['LangCode'], 2);
            $writer->putData($record['CountryCode'], 2);
            $writer->putUI32BE(0); // size
            $writer->putUI32BE(0); // offset
        }
        $recordOffsetCurr = $recordOffset;
        foreach ($this->records as $record) {
            $string = $record['String'];
            $size = strlen($string);
            list($offset, $dummy) = $writer->getOffset();
            $writer->setUI32BE($size, $recordOffsetCurr + 4);
            $writer->setUI32BE($offset, $recordOffsetCurr + 8);
            $writer->putData($string);
            $recordOffsetCurr += 12;
        }
    	return $writer->output();
    }
}
