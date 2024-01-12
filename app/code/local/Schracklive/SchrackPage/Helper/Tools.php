<?php

class Schracklive_SchrackPage_Helper_Tools extends Mage_Core_Helper_Abstract {

    public function cleanupDeprecatedRessources() {
        $jsBasePath = 'skin/frontend/schrack/default/schrackdesign/Public/Javascript/';
        $jsFiles = array_diff(scandir($jsBasePath), array('.', '..'));

        $cssBasePath = 'skin/frontend/schrack/default/schrackdesign/Public/Stylesheets/rwd/';
        $cssFiles = array_diff(scandir($cssBasePath), array('.', '..'));
        //Mage::log(getcwd(), null, 'ressources.log');

        if (is_array($jsFiles) && !empty($jsFiles)) {
            $allPackedJs = array();
            $commonPackedJs = array();
            $packedFooterJs = array();
            $packedOpcheckoutJs = array();

            foreach ($jsFiles as $index => $filename) {
                if (stristr($filename, 'allPacked.js')
                    && !stristr($filename, 'map')
                    && $filename != 'allPacked.js') {
                    $allPackedJs[] = $filename;
                }
                if (stristr($filename, 'commonPacked.js')
                    && !stristr($filename, 'map')
                    && $filename != 'commonPacked.js') {
                    $commonPackedJs[] = $filename;
                }
                if (stristr($filename, 'packedFooter.js')
                    && !stristr($filename, 'map')
                    && $filename != 'packedFooter.js') {
                    $packedFooterJs[] = $filename;
                }
                if (stristr($filename, 'packedOpcheckout.js')
                    && !stristr($filename, 'map')
                    && $filename != 'packedOpcheckout.js') {
                    $packedOpcheckoutJs[] = $filename;
                }
            }
        }

        arsort($allPackedJs);
        $sortedAllPackedJs = array_values($allPackedJs);
        $this->loopDeletionInactive($sortedAllPackedJs, $jsBasePath);

        arsort($commonPackedJs);
        $sortedCommonPackedJs = array_values($commonPackedJs);
        $this->loopDeletionInactive($sortedCommonPackedJs, $jsBasePath);

        arsort($packedFooterJs);
        $sortedPackedFooterJs = array_values($packedFooterJs);
        $this->loopDeletionInactive($sortedPackedFooterJs, $jsBasePath);

        arsort($packedOpcheckoutJs);
        $sortedPackedOpcheckoutJs = array_values($packedOpcheckoutJs);
        $this->loopDeletionInactive($sortedPackedOpcheckoutJs, $jsBasePath);

        if (is_array($cssFiles) && !empty($cssFiles)) {
            $allPackedCss = array();

            foreach ($cssFiles as $index => $filename) {
                if (stristr($filename, 'allPacked.css')
                    && !stristr($filename, 'map')
                    && $filename != 'allPacked.css') {
                    $allPackedCss[] = $filename;
                }
            }

            arsort($allPackedCss);
            $sortedAllPackedCss = array_values($allPackedCss);
            $this->loopDeletionInactive($sortedAllPackedCss, $cssBasePath);
        }

        //Mage::log($sortedAllPackedJs, null, 'ressources.log');

        return true;
    }


    private function loopDeletionInactive($allFiles, $ressourcePath) {
        if (is_array($allFiles) && !empty($allFiles)) {
            foreach ($allFiles as $index => $filename ) {
                // Don't delete the current file only:
                if ($index == 0) continue;
                chmod($ressourcePath, 0777); // default = 0570 (set recursively from mage_perms.sh)
                $result = unlink($ressourcePath . $filename);
                chmod($ressourcePath, 0570);
                //Mage::log($ressourcePath . $filename . ' deleted = ' . $result, null, 'ressources.log');
            }
        }
    }

}
