<?php

namespace UncleCheese\DropZone;


use Embed\Adapters\File;
use SilverStripe\Assets\Folder;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldFilterHeader;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\GridField\GridFieldSortableHeader;
use SilverStripe\Forms\TreeDropdownField;
use SilverStripe\ORM\DataList;
use SilverStripe\View\ArrayData;
use SilverStripe\View\SSViewer;

class FileAttachmentField_SelectHandler {
	
	private static $allowed_actions = array (
		'filesbyid',
	);
	
	/**
	 * @param $folderID The ID of the folder to display.
	 * @return FormField
	 */
	protected function getListField($folderID) {
		// Generate the folder selection field.
		$folderField = new TreeDropdownField('ParentID', _t('HtmlEditorField.FOLDER', Folder::class), Folder::class);
		$folderField->setValue($folderID);
		
		// Generate the file list field.
		$config = GridFieldConfig::create();
		$config->addComponent(new GridFieldSortableHeader());
		$config->addComponent(new GridFieldFilterHeader());
		$config->addComponent($columns = new GridFieldDataColumns());
		$columns->setDisplayFields(array(
			'StripThumbnail' => '',
			'Name' => 'Name',
			'Title' => 'Title'
		));
		$config->addComponent(new GridFieldPaginator(8));
		
		// If relation is to be autoset, we need to make sure we only list compatible objects.
		$baseClass = $this->parent->getFileClass();
		
		// Create the data source for the list of files within the current directory.
		$files = DataList::create($baseClass)->filter('ParentID', $folderID);
		
		$fileField = new GridField('Files', false, $files, $config);
		$fileField->setAttribute('data-selectable', true);
		if($this->parent->IsMultiple()) {
			$fileField->setAttribute('data-multiselect', true);
		}
		
		$selectComposite = new CompositeField(
			$folderField,
			$fileField
		);
		
		return $selectComposite;
	}
	
	public function filesbyid(HTTPRequest $r) {
		$ids = $r->getVar('ids');
		$files = File::get()->byIDs(explode(',',$ids));
		
		$validIDs = array();
		$json = array ();
		foreach($files as $file) {
			$template = new SSViewer('FileAttachmentField_attachments');
			$html = $template->process(ArrayData::create(array(
				'File' => $file,
				'Scope' => $this->parent
			)));
			
			$validIDs[$file->ID] = $file->ID;
			$json[] = array (
				'id' => $file->ID,
				'html' => $html->forTemplate()
			);
		}
		
		$this->parent->addValidFileIDs($validIDs);
		return Convert::array2json($json);
	}
	
}
