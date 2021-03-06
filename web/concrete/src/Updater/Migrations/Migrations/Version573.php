<?php
namespace Concrete\Core\Updater\Migrations\Migrations;

use Concrete\Core\Permission\Access\Entity\Type;
use Concrete\Core\Permission\Category;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use \Concrete\Core\Conversation\FlagType\FlagType;
use Concrete\Core\Block\BlockType\BlockType;
use AuthenticationType;
use Exception;
use Page;
use SinglePage;
use Concrete\Core\Permission\Key\Key as PermissionKey;

class Version573 extends AbstractMigration
{

    public function getName()
    {
        return '20141217000000';
    }

    public function up(Schema $schema)
    {
        $ft = FlagType::getByhandle('spam');
        if (!is_object($ft)) {
            FlagType::add('spam');
        }

        $bt = BlockType::getByHandle('image_slider');
        $bt->refresh();

        $types = array(Type::getByHandle('group'), Type::getByHandle('user'), Type::getByHandle('group_set'), Type::getByHandle('group_combination'));
        $categories = array(Category::getByHandle('conversation'), Category::getByHandle('conversation_message'));
        foreach($categories as $category) {
            foreach($types as $pe) {
                if (is_object($category) && is_object($pe)) {
                    $category->associateAccessEntityType($pe);
                }
            }
        }

        try {
            $gat = AuthenticationType::getByHandle('google');
        } catch(Exception $e) {
            $gat = AuthenticationType::add('google', 'Google');
            if (is_object($gat)) {
                $gat->disable();
            }
        }

        // fix register page permissions
		$g1 = \Group::getByID(GUEST_GROUP_ID);
        $register = \Page::getByPath('/register', "RECENT");
        $register->assignPermissions($g1, array('view_page'));

        // add new permission, set it to the same value as edit page permissions on all pages.
        $epk = PermissionKey::getByHandle('edit_page_permissions');
        $msk = PermissionKey::getByHandle('edit_page_multilingual_settings');
        if (!is_object($msk)) {
            $msk = PermissionKey::add('page', 'edit_page_multilingual_settings', 'Edit Multilingual Settings', 'Controls whether a user can see the multilingual settings menu, re-map a page or set a page as ignored in multilingual settings.', false, false);
        }
        $db = \Database::get();
        $r = $db->Execute('select cID from Pages where cInheritPermissionsFrom = "OVERRIDE" order by cID asc');
        while ($row = $r->FetchRow()) {
            $c = Page::getByID($row['cID']);
            if (is_object($c) && !$c->isError()) {
                $epk->setPermissionObject($c);
                $msk->setPermissionObject($c);
                $rpa = $epk->getPermissionAccessObject();
                if (is_object($rpa)) {
                    $pt = $msk->getPermissionAssignmentObject();
                    if (is_object($pt)) {
                        $pt->clearPermissionAssignment();
                        $pt->assignPermissionAccess($rpa);
                    }
                }
            }
        }

        // add new multilingual tables.
        $mpr = $schema->createTable('MultilingualPageRelations');
        $mpr->addColumn('mpRelationID', 'integer', array('notnull' => true, 'unsigned' => true, 'default' => 0));
        $mpr->addColumn('cID', 'integer', array('notnull' => true, 'unsigned' => true, 'default' => 0));
        $mpr->addColumn('mpLanguage', 'string', array('notnull' => true));
        $mpr->addColumn('mpLocale', 'string', array('notnull' => true));
        $mpr->setPrimaryKey(array('mpRelationID', 'cID', 'mpLocale'));

        $mus = $schema->createTable('MultilingualSections');
        $mus->addColumn('cID', 'integer', array('notnull' => true, 'unsigned' => true, 'default' => 0));
        $mus->addColumn('msLanguage', 'string', array('notnull' => true, 'default' => ''));
        $mus->addColumn('msCountry', 'string', array('notnull' => true, 'default' => ''));
        $mus->setPrimaryKey(array('cID'));

        $mts = $schema->createTable('MultilingualTranslations');
        $mts->addColumn('mtID', 'integer', array('autoincrement' => true, 'unsigned' => true));
        $mts->addColumn('mtSectionID', 'integer', array('unsigned' => true, 'notnull' => true, 'default' => 0));
        $mts->addColumn('msgid', 'text');
        $mts->addColumn('msgstr', 'text');
        $mts->addColumn('context', 'text');
        $mts->addColumn('reference', 'text');
        $mts->addColumn('flags', 'text');
        $mts->addColumn('updated', 'datetime');
        $mts->setPrimaryKey(array('mtID'));

        // block type
        $bt = BlockType::getByHandle('switch_language');
        if (!is_object($bt)) {
            $bt = BlockType::installBlockType('switch_language');
        }

        // single pages
        $sp = Page::getByPath('/dashboard/system/multilingual');
        if (!is_object($sp) || $sp->isError()) {
            $sp = SinglePage::add('/dashboard/system/multilingual');
            $sp->update(array('cName' => 'Multilingual'));
            $sp->setAttribute('meta_keywords', 'multilingual, localization, internationalization, i18n');
        }
        $sp = Page::getByPath('/dashboard/system/multilingual/setup');
        if (!is_object($sp) || $sp->isError()) {
            $sp = SinglePage::add('/dashboard/system/multilingual/setup');
            $sp->update(array('cName' => 'Multilingual Setup'));
        }
        $sp = Page::getByPath('/dashboard/system/multilingual/page_report');
        if (!is_object($sp) || $sp->isError()) {
            $sp = SinglePage::add('/dashboard/system/multilingual/page_report');
            $sp->update(array('cName' => 'Page Report'));
        }
        $sp = Page::getByPath('/dashboard/system/multilingual/translate_interface');
        if (!is_object($sp) || $sp->isError()) {
            $sp = SinglePage::add('/dashboard/system/multilingual/translate_interface');
            $sp->update(array('cName' => 'Translate Interface'));
        }

    }

    public function down(Schema $schema)
    {
    }
}
