<?php

require_once dirname(__FILE__).'/../Bit.php';
require_once dirname(__FILE__).'/Base.php';
require_once dirname(__FILE__).'/Curve.php';

class IO_ICC_Type_MFAB extends IO_ICC_Type_Base {
    const DESCRIPTION = 'MultiFunction AtoB Table';
    var $_iccInfo = null;
    var $type = null;
    var $nInput, $nOutput;
    var $bCurve = null;
    function __construct($iccInfo) {
        $this->_iccInfo = $iccInfo;
    }
    function parseContent($content, $opts = array()) {
        $reader = new IO_ICC_Bit();
    	$reader->input($content);
        $this->type = $reader->getData(4);
        $reader->incrementOffset(4, 0); // skip
        //
        $this->nInput = $reader->getUI8();
        $this->nOutput = $reader->getUI8();
        $reader->incrementOffset(2, 0); // reserved padding
        $offsetToBCurve = $reader->getUI32BE();
        $offsetToMatrix = $reader->getUI32BE();
        $offsetToMCurve = $reader->getUI32BE();
        $offsetToCLUT = $reader->getUI32BE();
        $offsetToACurve = $reader->getUI32BE();
        //        var_dump($offsetToBCurve, $offsetToMatrix, $offsetToMCurve, $offsetToCLUT, $offsetToACurve);
        $reader->setOffset($offsetToBCurve, 0);
        $bCurveContent = $reader->getData($offsetToMatrix - $offsetToBCurve);
        $bCurves = array();
        for ($i = 0 ; $i < $this->nInput; $i++ ) {
            $bCurve = IO_ICC_Type::makeType($bCurveContent, $this->_iccInfo);
            if ($bCurve === false) {
                break;
            }
            $bCurve->parseContent($bCurveContent);
            $bCurves []= $bCurve;
            $bCurveContent = substr($bCurveContent, $bCurve->getContentLength());
        }
        $this->bCurves = $bCurves;

    }

    function dumpContent($opts = array()) {
        $this->echoIndentSpace($opts);
        echo "nInput:{$this->nInput} nOutput:{$this->nOutput}".PHP_EOL;
        $this->echoIndentSpace($opts);
        echo "bCurves:".PHP_EOL;
        foreach ($this->bCurves as $bCurve) {
            $opts2 = array_merge($opts, array('level' => $opts['level']+1));
            $bCurve->dumpContent($opts2);
        }
    }

    function buildContent($opts = array()) {
        $writer = new IO_Bit();
        $writer->putData($this->type);
        $writer->putData("\0\0\0\0");
        //
        foreach ($this->values as $value) {
            $writer->putS15Fixed16Number($value);
        }
    	return $writer->output();
    }
}
