<?php

/**
 * HtmlAccordionModule.  Here we are extending an existing theme.
 * Instead, you could extend AbstractModule and implement ModuleThemeInterface directly.
 */

declare(strict_types=1);

namespace TreesListModule;

use Fisharebest\Webtrees\Module\HtmlBlockModule<?php



declare(strict_types=1);

namespace TreesListModule;

use Fisharebest\Webtrees\Module\HtmlBlockModule;
use Fisharebest\Webtrees\Module\ModuleCustomInterface;
use Fisharebest\Webtrees\Module\ModuleBlockInterface;
use Fisharebest\Webtrees\Module\ModuleGlobalInterface;
use Fisharebest\Webtrees\Module\ModuleCustomTrait;
use Fisharebest\Webtrees\Module\ModuleBlockTrait;
use Fisharebest\Webtrees\Module\ModuleGlobalTrait;
use Fisharebest\Webtrees\Module\ModuleServerRequestTrait;
use Fisharebest\Webtrees\Services\HtmlService;
use Fisharebest\Webtrees\Tree;
use Fisharebest\Webtrees\I18N;
//use Fisharebest\Webtrees\Validator;
use Illuminate\Support\Str;
use Psr\Http\Message\ServerRequestInterface;
use Fisharebest\Webtrees\View;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Query\Expression;


use function in_array;
use function time;

class TreesListModule extends HtmlBlockModule implements ModuleCustomInterface,ModuleBlockInterface, ModuleGlobalInterface
{
   
    use ModuleCustomTrait;
    use ModuleGlobalTrait;
    use ModuleBlockTrait;

    public const CUSTOM_MODULE         = 'Family-Trees-List';
    public const CUSTOM_GITHUB_USER = 'iyoua';
    public const CUSTOM_WEBSITE          = 'https://github.com/' . self::CUSTOM_GITHUB_USER . '/' . self::CUSTOM_MODULE . '/';
       
    
    
    public function resourcesFolder(): string
    {
        return __DIR__ .  DIRECTORY_SEPARATOR .  'resources' .  DIRECTORY_SEPARATOR;
        
    }
    

    public function boot(): void
    {
        View::registerNamespace($this->name(), $this->resourcesFolder() . 'views/');
        View::registerCustomView('::trees-table', $this->name() . '::trees-table');
    }
        
    

    public function headContent(): string
    {
        return '<link rel="stylesheet" href="' . e($this->assetUrl('css/treeslist.css')) . '">';
        
    }
    

    public function title(): string
    {
        /* I18N: Name of a module */
        return I18N::translate('FamilyTree List');
    }


    public function description(): string
    {        
        return I18N::translate('List of FamilyTree on Website');
        
    }


    public function customModuleAuthorName(): string
    {
        return 'iyoua';
    }
    

    public function customModuleSupportUrl(): string
    {
        return self::CUSTOM_WEBSITE;
    }


    public function getBlock(Tree $tree, int $block_id, string $context, array $config = []): string
    {
    
$gedcoms = DB::table('gedcom')    
    ->select([    
        'gedcom.gedcom_name',    
        DB::raw("(SELECT setting_value FROM wtgh_gedcom_setting WHERE gedcom_id = wtgh_gedcom.gedcom_id AND setting_name = 'title') AS Title"),
        DB::raw("(SELECT COUNT(*) FROM wtgh_individuals WHERE i_file = wtgh_gedcom.gedcom_id) AS Individuals"),   
        DB::raw("(SELECT COUNT(*) FROM wtgh_families WHERE f_file = wtgh_gedcom.gedcom_id) AS Families"),  
        DB::raw("(SELECT COUNT(*) FROM wtgh_dates WHERE d_file = wtgh_gedcom.gedcom_id) AS Events"),  
        DB::raw("(SELECT COUNT(DISTINCT n_surn) FROM wtgh_name WHERE n_file = wtgh_gedcom.gedcom_id) AS Surnames"),   
    ])    
    ->where('gedcom.gedcom_id', '>', 0) 
    ->orderBy('Individuals','desc')  
    ->get();



        $content = view('trees-table', [
                    'block_id' =>$block_id,
                    'gedcoms'  => $gedcoms,
                    'familyicon' => $this->assetUrl('images/family.png'),
			    	'individualicon' => $this->assetUrl('images/person.png'),
			    	'eventicon' => $this->assetUrl('images/event.png'),
			    	'surnameicon' => $this->assetUrl('images/surname.png'),

                ]);

        if ($context !== self::CONTEXT_EMBED) {

			$title = I18N::translate('List of FamilyTree on Website');
            return view('modules/block-template', [
                'block'      => Str::kebab($this->name()),
                'id'         => $block_id,
				'config_url' => '',
                'title'      => $title,
                'content'    => $content,
            ]);
        }

        return $content;
    }


    public function loadAjax(): bool
    {
        return false;
    }
   

    /**
     * Can this block be shown on the user’s home page?
     *
     * @return bool
     */
    public function isUserBlock(): bool
    {
        return false;
    }

    /**
     * Can this block be shown on the tree’s home page?
     *
     * @return bool
     */
    public function isTreeBlock(): bool
    {
        return true;
    }
    
    public function customModuleVersion(): string
    {
        return '1.1.0';
    }
    
        public function customTranslations(string $language): array
    {
        //  
        switch ($language) {
            case 'de':
                return $this->germanTranslations();
            
            case 'zh-Hans':
                return $this->hansTranslations();
                
            case 'zh-Hant':
                return $this->hantTranslations();    
                
            default:
                return [];
        }
    }
    
    
    protected function germanTranslations(): array
    {
        // Note the special characters used in plural and context-sensitive translations.
        return [
            'List of FamilyTree on Website' => 'Diese Website enthält einen Stammbaum',
            'Total number of households' => 'Gesamtzahl der Haushalte',
            'Total number of Individuals' => 'Gesamtzahl der Familienangehörigen',
            'Total number of Events' => 'Gesamtzahl der Veranstaltungen',
            'Total number of Surnames' => 'Nachnamen insgesamt',
            'FamilyTree List' => 'Genealogische Liste',
        ];
    }

    protected function hansTranslations() : array
    {
        // 
        return [
            'List of FamilyTree on Website' => '本网站已收录家谱',
            'Total number of households' => '家庭总数',
            'Total number of Individuals' => '家族成员总数',
            'Total number of Events' => '事件总数',
            'Total number of Surnames' => '姓氏总数',
            'FamilyTree List' => '家谱列表',
        ];
    }
    
    protected function hantTranslations() : array
    {
        
        return [
            'List of FamilyTree on Website' => '本網站已收錄家譜',
            'Total number of households' => '家庭總數',
            'Total number of Individuals' => '家族成員總數',
            'Total number of Events' => '事件總數',
            'Total number of Surnames' => '姓氏總數',
            'FamilyTree List' => '家譜列表',
        ];
    }

}
;
use Fisharebest\Webtrees\Module\ModuleCustomInterface;
use Fisharebest\Webtrees\Module\ModuleBlockInterface;
use Fisharebest\Webtrees\Module\ModuleGlobalInterface;
use Fisharebest\Webtrees\Module\ModuleCustomTrait;
use Fisharebest\Webtrees\Module\ModuleBlockTrait;
use Fisharebest\Webtrees\Module\ModuleGlobalTrait;
use Fisharebest\Webtrees\Module\ModuleServerRequestTrait;
use Fisharebest\Webtrees\Services\HtmlService;
use Fisharebest\Webtrees\Tree;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Validator;
use Illuminate\Support\Str;
use Psr\Http\Message\ServerRequestInterface;
use Fisharebest\Webtrees\View;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Query\Expression;


use function in_array;
use function time;

class TreesListModule extends HtmlBlockModule implements ModuleCustomInterface,ModuleBlockInterface, ModuleGlobalInterface
{
   
    use ModuleCustomTrait;
    use ModuleGlobalTrait;
    use ModuleBlockTrait;

    public const CUSTOM_MODULE         = 'Family-Trees-List';
    public const CUSTOM_GITHUB_USER = 'iyoua';
    public const CUSTOM_WEBSITE          = 'https://github.com/' . self::CUSTOM_GITHUB_USER . '/' . self::CUSTOM_MODULE . '/';
       
    private HtmlService $html_service;

    /**
     * HtmlBlockModule bootstrap.
     *
     * @param HtmlService $html_service
     */
    
   public function __construct(HtmlService $html_service)
    {        
      parent::__construct($html_service);  
      $this->html_service = $html_service;
    }  
    
    
    public function resourcesFolder(): string
    {
        return __DIR__ .  DIRECTORY_SEPARATOR .  'resources' .  DIRECTORY_SEPARATOR;
        
    }
    
    /**
     * Bootstrap.  This function is called on *enabled* modules.
     * It is a good place to register routes and views.
     * Note that it is only called on genealogy pages - not on admin pages.
     *
     * @return void
     */
    public function boot(): void
    {
        View::registerNamespace($this->name(), $this->resourcesFolder() . 'views/');
        View::registerCustomView('::trees-table', $this->name() . '::trees-table');
    }
        
    
    /**
     * Raw content, to be added at the end of the <head> element.
     * Typically, this will be <link> and <meta> elements.
     *
     * @return string
     */
    public function headContent(): string
    {
        return '<link rel="stylesheet" href="' . e($this->assetUrl('css/treeslist.css')) . '">';
        
    }
    
    
    /**
     * How should this module be identified in the control panel, etc.?
     *
     * @return string
     */
    public function title(): string
    {
        /* I18N: Name of a module */
        return I18N::translate('FamilyTree List');
    }

    /**
     * A sentence describing what this module does.
     *
     * @return string
     */
    public function description(): string
    {        
        return I18N::translate('List of FamilyTree on Website');
        
    }

   
    /**
     * The person or organisation who created this module.
     *
     * @return string
     */
    public function customModuleAuthorName(): string
    {
        return 'iyoua';
    }
    
   /**
     * Where to get support for this module.  Perhaps a github respository?
     *
     * @return string
     */
    public function customModuleSupportUrl(): string
    {
        return self::CUSTOM_WEBSITE;
    }

    /**
     * Generate the HTML content of this block.
     *
     * @param Tree                 $tree
     * @param int                  $block_id
     * @param string               $context
     * @param array<string,string> $config
     *
     * @return string
     */
    public function getBlock(Tree $tree, int $block_id, string $context, array $config = []): string
    {
    
$gedcoms = DB::table('gedcom')    
    ->select([    
        'gedcom.gedcom_name',    
        DB::raw("(SELECT setting_value FROM wtgh_gedcom_setting WHERE gedcom_id = wtgh_gedcom.gedcom_id AND setting_name = 'title') AS Title"),
        DB::raw("(SELECT COUNT(*) FROM wtgh_individuals WHERE i_file = wtgh_gedcom.gedcom_id) AS Individuals"),   
        DB::raw("(SELECT COUNT(*) FROM wtgh_families WHERE f_file = wtgh_gedcom.gedcom_id) AS Families"),  
        DB::raw("(SELECT COUNT(*) FROM wtgh_dates WHERE d_file = wtgh_gedcom.gedcom_id) AS Events"),  
        DB::raw("(SELECT COUNT(DISTINCT n_surn) FROM wtgh_name WHERE n_file = wtgh_gedcom.gedcom_id) AS Surnames"),   
    ])    
    ->where('gedcom.gedcom_id', '>', 0) 
    ->orderBy('Individuals','desc')  
    ->get();

        // Find a module providing individual lists.
        $module = 'Trees';
        $content = view('trees-table', [
                    'block_id' =>$block_id,
                    'module'   => $module,
                    'gedcoms'  => $gedcoms,
                    'familyicon' => $this->assetUrl('images/family.png'),
			    	'individualicon' => $this->assetUrl('images/person.png'),
			    	'eventicon' => $this->assetUrl('images/event.png'),
			    	'surnameicon' => $this->assetUrl('images/surname.png'),

                ]);

        if ($context !== self::CONTEXT_EMBED) {

			$title = I18N::translate('List of FamilyTree on Website');
            return view('modules/block-template', [
                'block'      => Str::kebab($this->name()),
                'id'         => $block_id,
				'config_url' => '',
                'title'      => $title,
                'content'    => $content,
            ]);
        }

        return $content;
    }



    
    /**
     * Should this block load asynchronously using AJAX?
     *
     * Simple blocks are faster in-line, more complex ones can be loaded later.
     *
     * @return bool
     */
    public function loadAjax(): bool
    {
        return false;
    }
   

    /**
     * Can this block be shown on the user’s home page?
     *
     * @return bool
     */
    public function isUserBlock(): bool
    {
        return true;
    }

    /**
     * Can this block be shown on the tree’s home page?
     *
     * @return bool
     */
    public function isTreeBlock(): bool
    {
        return true;
    }
    
    public function customModuleVersion(): string
    {
        return '1.0.0';
    }
    
        public function customTranslations(string $language): array
    {
        // Here we are using an array for translations.
        // If you had .MO files, you could use them with:
        // return (new Translation('path/to/file.mo'))->asArray();
        switch ($language) {
            
            case 'zh-Hans':
                return $this->hansTranslations();
                
            case 'zh-Hant':
                return $this->hantTranslations();    
                
            default:
                return [];
        }
    }
    

    protected function hansTranslations() : array
    {
        // Note the special characters used in plural and context-sensitive translations.
        return [
            'List of FamilyTree on Website' => '本网站已收录家族',
            'Number of Families' => '家庭总数',
            'Number of Individuals' => '家族成员总数',
            'Number of Events' => '事件总数',
            'Number of Surnames' => '姓氏总数',
            'FamilyTree List' => '家谱列表',
        ];
    }
    
    protected function hantTranslations() : array
    {
        // Note the special characters used in plural and context-sensitive translations.
        return [
            'List of FamilyTree on Website' => '本網站已收錄家譜',
            'Total number of households' => '家庭總數',
            'Total number of Individuals' => '家族成員總數',
            'Total number of Events' => '事件總數',
            'Total number of Surnames' => '姓氏總數',
            'FamilyTree List' => '家譜列表',
        ];
    }

}
