<?php

class user_tools {

	function user_setTableClass($content, $conf) {

		$layout = $this->cObj->data['layout'];

		/* 244 -> see typoscript setup (page ts) */
		/*
					   if ($layout == 244) {
								$content = str_replace('<table class="contenttable contenttable-244"', '<table class="table-home contenttable contenttable-244" ', $content);
					   }
					   else {
		*/
		$pos1 = strpos($content, '<tr');
		$pos2 = strpos($content, '</tr>');

		if ($pos1 !== false && $pos2 !== false) {
			$analize_content = substr($content, $pos1 + strlen('<tr'), $pos2 - $pos1);
			$cells = (count(explode('<td ', $analize_content)) - 1);

			if ($cells) {
				$content = str_replace('<table class="contenttable contenttable-0"', '<table class="contenttable contenttable-0 table'.$cells.'"', $content);
			}
		}

//               }

		return $content;
	}
}

?>
