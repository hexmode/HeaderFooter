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

		$nsheader = self::conditionalInclude( $text, '__NONSHEADER__', 'hf-nsheader', $ns );
		$header   = self::conditionalInclude( $text, '__NOHEADER__',   'hf-header', $name );
		$footer   = self::conditionalInclude( $text, '__NOFOOTER__',   'hf-footer', $name );
		$nsfooter = self::conditionalInclude( $text, '__NONSFOOTER__', 'hf-nsfooter', $ns );

		$parserOutput->setText( $nsheader . $header . $text . $footer . $nsfooter );

		global $egHeaderFooterEnableAsyncHeader, $egHeaderFooterEnableAsyncFooter;
		if ( $egHeaderFooterEnableAsyncFooter || $egHeaderFooterEnableAsyncHeader ) {
			$op->addModules( 'ext.headerfooter.dynamicload' );
		}

		return true;
	}

	/**
	 * Verifies & Strips ''disable command'', returns $content if all OK.
	 */
	static function conditionalInclude( &$text, $disableWord, $class, $unique ) {

		// is there a disable command lurking around?
		$disable = strpos( $text, $disableWord ) !== false;

		// if there is, get rid of it
		// make sure that the disableWord does not break the REGEX below!
		$text = preg_replace('/'.$disableWord.'/si', '', $text );

		// if there is a disable command, then don't return anything
		if ( $disable ) {
			return null;
		}

		$msgId = "$class-$unique"; // also HTML ID
		$div = "<div class='$class' id='$msgId'>";

		global $egHeaderFooterEnableAsyncHeader, $egHeaderFooterEnableAsyncFooter;

		$isHeader = $class === 'hf-nsheader' || $class === 'hf-header';
		$isFooter = $class === 'hf-nsfooter' || $class === 'hf-footer';

		if ( ( $egHeaderFooterEnableAsyncFooter && $isFooter )
			|| ( $egHeaderFooterEnableAsyncHeader && $isHeader ) ) {

			// Just drop an empty div into the page. Will fill it with async
			// request after page load
			return $div . '</div>';
		}
		else {
			$msgText = wfMessage( $msgId )->parse();

			// don't need to bother if there is no content.
			if ( empty( $msgText ) ) {
				return null;
			}

			if ( wfMessage( $msgId )->inContentLanguage()->isBlank() ) {
				return null;
			}

			return $div . $msgText . '</div>';
		}
	}

	public static function onResourceLoaderGetConfigVars ( array &$vars ) {
		global $egHeaderFooterEnableAsyncHeader, $egHeaderFooterEnableAsyncFooter;

		$vars['egHeaderFooter'] = [
			'enableAsyncHeader' => $egHeaderFooterEnableAsyncHeader,
			'enableAsyncFooter' => $egHeaderFooterEnableAsyncFooter,
		];

		return true;
	}

}
