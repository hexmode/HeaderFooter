<?php
/**
 * @package HeaderFooter
 */
class HeaderFooter
{
	protected static function shouldUse( OutputPage $out ) {
		$action
			= $out->parserOptions()->getUser()->getRequest()->getVal("action");
		if (
			($action === 'edit') ||
			($action === 'submit') ||
			($action === 'history') )
		{
			return false;
		}
		return true;
	}

	/* This is only used on my hacked Vector skin and should disappear */
	public static function onSkinOutBeforePersonalTools( BaseTemplate $tpl ) {
		$ctx = new RequestContext();
		$title = $ctx->getTitle();
		$ns = $title->getNsText();
		$msgNs = wfMessage( 'hf-top-header-' . $ns );
		$msg = wfMessage( 'hf-top-header' );
		if ( $msg->isDisabled() && $msgNs->isDisabled() ) {
			return true;
		}
		$msgText = !$msgNs->isDisabled()
				 ? $msgNs->inContentLanguage()
				 : $msg->inContentLanguage();

		echo $msgText;
		return true;
	}

	public static function onSkinTemplateOutputPageBeforeExec(
		SkinTemplate $skin,
		BaseTemplate $tpl
	) {
		$out = $skin->getOutput();
		if ( !self::shouldUse( $out ) ) {
			return true;
		}
		$msgText = wfMessage( 'hf-top-header' )->inContentLanguage();
		if ( $msgText->isDisabled() ) {
			return true;
		}
		if ( $skin->getSkinName() !== 'foreground' ) {
			return true;
		}
		$topHeader = '<div id="hf-top-header">' . $msgText . '</div>';
		$tpl->set( 'headelement', $tpl->get( 'headelement' ) . $topHeader );
		return true;
	}

	/**
	 * Main Hook
	 */
	public static function hOutputPageParserOutput( &$op, $parserOutput ) {
		if ( !self::shouldUse( $op ) ) {
			return true;
		}
		$title = $op->getTitle();
		$ns = $title->getNsText();
		$name = $title->getPrefixedDBKey();

		$text = $parserOutput->getText();

		$nsheader = "hf-nsheader-$ns";
		$nsfooter = "hf-nsfooter-$ns";

		$header = "hf-header-$name";
		$footer = "hf-footer-$name";

		$text = '<div class="hf-header">' .
			  self::conditionalInclude( $text, '__NOHEADER__', $header ) .
			  '</div>'.$text;
		$text = '<div class="hf-nsheader">' .
			  self::conditionalInclude( $text, '__NONSHEADER__', $nsheader ) .
			  '</div>'.$text;
		$text .= '<div class="hf-footer">' .
			  self::conditionalInclude( $text, '__NOFOOTER__', $footer ) .
			  '</div>';
		$text .= '<div class="hf-nsfooter">' .
			  self::conditionalInclude( $text, '__NONSFOOTER__', $nsfooter ) .
			  '</div>';

		$parserOutput->setText( $text );

		return true;
	}

	/**
	 * Verifies & Strips ''disable command'', returns $content if all OK.
	 */
	static function conditionalInclude( &$text, $disableWord, &$msgId ) {

		// is there a disable command lurking around?
		$disable = strpos( $text, $disableWord ) !== false;

		// if there is, get rid of it
		// make sure that the disableWord does not break the REGEX below!
		$text = preg_replace('/'.$disableWord.'/si', '', $text );

		// if there is a disable command, then don't return anything
		if ( $disable ) {
			return null;
		}

		$msgText = wfMessage( $msgId )->parse();

		// don't need to bother if there is no content.
		if ( empty( $msgText ) ) {
			return null;
		}

		if ( wfMessage( $msgId )->inContentLanguage()->isBlank() ) {
			return null;
		}

		return $msgText;
	}

}