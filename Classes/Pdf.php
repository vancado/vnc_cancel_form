<?php

namespace Vancado\VncCancelForm;

use In2code\Powermail\Domain\Model\Mail;
use TYPO3\CMS\Core\Error\Exception;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Pdf extends \Undkonsorten\Powermailpdf\Pdf
{
    protected $encoding = false;





    protected function encodeValue($value) {
        if($value == '') $value = 'k.A.';
        if ($this->encoding) {
            return iconv('UTF-8', $this->encoding, $value);
        } else {
            return $value;
        }
    }

    /**
     * @param Mail $mail
     * @return File
     * @throws Exception
     */
    protected function generatePdf(Mail $mail)
    {

        $settings = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_powermailpdf.']['settings.'];
        $this->encoding = $settings['encoding'];

        /** @var Folder $folder */
        $folder = ResourceFactory::getInstance()->getFolderObjectFromCombinedIdentifier($settings['target.']['pdf']);

        // Include \FPDM library from phar file, if not included already (e.g. composer installation)
        if (!class_exists('\FPDM')) {
            @include 'phar://' . ExtensionManagementUtility::extPath('powermailpdf') . 'Resources/Private/PHP/fpdm.phar/vendor/autoload.php';
        }
       

        //Normal Fields
        $fieldMap = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_powermailpdf.']['settings.']['fieldMap.'];

        $answers = $mail->getAnswers();

        $fdfDataStrings = array();
        $pdfField_value = null;
        foreach ($fieldMap as $fieldID => $fieldConfig) {
            foreach ($answers as $answer) {


                $pdfField_name = explode('.', $fieldID)[0];

                if (is_array($fieldConfig)) {
                    $pdfField_type = $fieldConfig['type'];
                    $pdfField_value = $fieldConfig['form_value'];
                    $formField_name = $fieldConfig['form_name'];
                } else {
                    $pdfField_type = 'text';
                    $formField_name = $fieldConfig;
                }

                if ($formField_name == $answer->getField()->getMarker()) {
                    if ($pdfField_type == 'text') {
                        $pdfField_value = $this->encodeValue($answer->getValue());

                    } else if ($pdfField_type == 'checkbox') {
                        if ($answer->getValue() == $fieldConfig['form_value']) {
                            $pdfField_value = $this->encodeValue($fieldConfig['pdf_value']);
                        }
                    } else if ($pdfField_type == 'radio') {
                        
                    }
                } else {
                    continue;
                }
                $fdfDataStrings[$pdfField_name] = $pdfField_value;

            }
        }



        $pdfOriginal = GeneralUtility::getFileAbsFileName($GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_powermailpdf.']['settings.']['sourceFile']);

        if (!empty($pdfOriginal)) {
            $pdfFlatTempFile = (string) null;
            $info = pathinfo($pdfOriginal);
            $pdfFilename = basename($pdfOriginal, '.' . $info['extension']) . '_';
            $pdfTempFile = GeneralUtility::tempnam($pdfFilename, '.pdf');

            $pdf = new \FPDM($pdfOriginal);
            $pdf->Load($fdfDataStrings, !$this->encoding); // second parameter: false if field values are in ISO-8859-1, true if UTF-8
            $pdf->Merge();
            $pdf->Output("F", GeneralUtility::getFileAbsFileName($pdfTempFile));

            if ($settings['flatten'] && $settings['flattenTool']) {
                $pdfFlatTempFile = GeneralUtility::tempnam($pdfFilename, '.pdf');
                switch ($settings['flattenTool']) {
                    case 'gs':
                        // Flatten PDF with ghostscript
                        @shell_exec("gs -sDEVICE=pdfwrite -dSubsetFonts=false -dPDFSETTINGS=/default -dNOPAUSE -dBATCH -sOutputFile=" . $pdfFlatTempFile . " " . $pdfTempFile);
                        break;
                    case 'pdftocairo':
                        // Flatten PDF with pdftocairo
                        @shell_exec('pdftocairo -pdf ' . $pdfTempFile . ' ' . $pdfFlatTempFile);
                        break;
                }
            }
        } else {
            throw new Exception("No pdf file is set in Typoscript. Please set tx_powermailpdf.settings.sourceFile if you want to use the filling feature.", 1417432239);
        }

        if (file_exists($pdfFlatTempFile)) {
            return $folder->addFile($pdfFlatTempFile);
        }

        return $folder->addFile($pdfTempFile);
    }
}
