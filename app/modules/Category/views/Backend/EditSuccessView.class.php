<?php

class Category_Backend_EditSuccessView extends XRXCategoryBackendView
{
	public function executeHtml(AgaviRequestDataHolder $rd)
	{
		$this->setupHtml($rd, 'redirect');

		$this->setAttribute('_url', $this->ro->gen('default', array('path'=>'admin/category')));
		$this->setAttribute('_type', 'success');
		$this->setAttribute('_title', $this->tm->_('category updated successfully', '.category'));
	}
}

?>