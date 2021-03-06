<?php

class Comment_Backend_EditInputView extends XRXCommentBackendView
{
	public function executeHtml(AgaviRequestDataHolder $rd)
	{
		$this->setupHtml($rd);

		// Set URL to redirect back to.
		$this->setAttribute('referer', $rd->getHeader('REFERER'));
		$this->setAttribute('_title', $this->tm->_('edit comment', '.comment'));
	}
}

?>