<?php

/**
 * Slimdown - A simple regex-based Markdown parser in PHP. Supports the
 * following elements (and can be extended via `Slimdown::add_rule()`):
 *
 * - Headers
 * - Links
 * - Bold
 * - Emphasis
 * - Deletions
 * - Quotes
 * - Code blocks
 * - Inline code
 * - Blockquotes
 * - Ordered/unordered lists
 * - Horizontal rules
 * - Images
 *
 * Author: Johnny Broadway <johnny@johnnybroadway.com>
 * Website: https://github.com/jbroadway/slimdown
 * License: MIT
 */
class Slimdown {
	public static $rules = array (
		'/```(.*?)```/s' => self::class .'::code_parse',                              // code blocks
		'/\n(#+)(.*)/' => self::class .'::header',                                   // headers
		'/\!\[([^\[]+)\]\(([^\)]+)\)/' => '<img src=\'\2\' alt=\'\1\' />',  // images
		'/\[([^\[]+)\]\(([^\)]+)\)/' => '<a href=\'\2\'>\1</a>',            // links
		'/(\*\*|__)(.*?)\1/' => '<strong>\2</strong>',                      // bold
		'/(\*|_)(.*?)\1/' => '<em>\2</em>',                                 // emphasis
		'/\~\~(.*?)\~\~/' => '<del>\2</del>',                               // del
		'/\:\"(.*?)\"\:/' => '<q>\1</q>',                                   // quote
		'/`(.*?)`/' => '<code>\1</code>',                                   // inline code
		'/\n\*(.*)/' => self::class .'::ul_list',                                    // ul lists
		'/\n[0-9]+\.(.*)/' => self::class .'::ol_list',                              // ol lists
		'/\n(&gt;|\>)(.*)/' => self::class .'::blockquote',                          // blockquotes
		'/\n-{5,}/' => "\n<hr />",                                          // horizontal rule
		'/\n([^\n]+)\n/' => self::class .'::para',                                   // add paragraphs
		'/<\/ul>\s?<ul>/' => '',                                            // fix extra ul
		'/<\/ol>\s?<ol>/' => '',                                            // fix extra ol
		'/<\/blockquote><blockquote>/' => "\n"                              // fix extra blockquote
	);
	
	private static function code_parse ($regs) {
		$item = $regs[1];
		$item = htmlentities ($item, ENT_COMPAT);
		$item = str_replace ("\n\n", '<br>', $item);
		$item = str_replace ("\n", '<br>', $item);
		while (mb_substr ($item, 0, 4) === '<br>') {
			$item = mb_substr ($item, 4);
		}
		while (mb_substr ($item, -4) === '<br>') {
			$item = mb_substr ($item, 0, -4);
		}
		return sprintf ("<pre><code>%s</code></pre>", trim ($item));
	}

	private static function para ($regs) {
		$line = $regs[1];
		$trimmed = trim ($line);
		if (preg_match ('/^<\/?(ul|ol|li|h|p|bl|table|tr|th|td|code)/', $trimmed)) {
			return "\n" . $line . "\n";
		}
		if (! empty ($trimmed)) {
			return sprintf ("\n<p>%s</p>\n", $trimmed);
		}
		return $trimmed;
	}

	private static function ul_list ($regs) {
		$item = $regs[1];
		return sprintf ("\n<ul>\n\t<li>%s</li>\n</ul>", trim ($item));
	}

	private static function ol_list ($regs) {
		$item = $regs[1];
		return sprintf ("\n<ol>\n\t<li>%s</li>\n</ol>", trim ($item));
	}

	private static function blockquote ($regs) {
		$item = $regs[2];
		return sprintf ("\n<blockquote>%s</blockquote>", trim ($item));
	}

	private static function header ($regs) {
		list ($tmp, $chars, $header) = $regs;
		$level = strlen ($chars);
		return sprintf ('<h%d>%s</h%d>', $level, trim ($header), $level);
	}

	/**
	 * Add a rule.
	 */
	public static function add_rule ($regex, $replacement) {
		self::$rules[$regex] = $replacement;
	}

	/**
	 * Render some Markdown into HTML.
	 */
	public static function render ($text) {
		$text = "\n" . $text . "\n";
		foreach (self::$rules as $regex => $replacement) {
			if (is_callable ( $replacement)) {
				$text = preg_replace_callback ($regex, $replacement, $text);
			} else {
				$text = preg_replace ($regex, $replacement, $text);
			}
		}
		return trim ($text);
	}
}
