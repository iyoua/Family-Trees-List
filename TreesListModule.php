<?php



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
use Fisharebest\Webtrees\Validator;
use Illuminate\Support\Str;
use Psr\Http\Message\ServerRequestInterface;
use Fisharebest\Webtrees\View;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\JoinClause;
use Fisharebest\Webtrees\Services\TreeService;
use Fisharebest\Webtrees\Http\RequestHandlers\TreePage;
use Illuminate\Support\Collection;


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
    public function customModuleLatestVersionUrl(): string { return 'https://github.com/iyoua/Family-Trees-List/blob/main/version.txt'; }
        // Default values for new blocks.
    private const DEFAULT_SORT = 'id_desc'; //默认排序方式：id
    private const DEFAULT_STYLE = 'list';   //默认布局：列表
       

    private TreeService $tree_service;
    public function __construct(TreeService $tree_service)
    {
        $this->tree_service = $tree_service;
    }
    
    public function resourcesFolder(): string
    {
        return __DIR__ .  DIRECTORY_SEPARATOR .  'resources' .  DIRECTORY_SEPARATOR;
        
    }
    

    public function boot(): void
    {
        View::registerNamespace($this->name(), $this->resourcesFolder() . 'views/');
        View::registerCustomView('::list', $this->name() . '::list');
        View::registerCustomView('::card', $this->name() . '::card');
        View::registerCustomView('::table', $this->name() . '::table');
        View::registerCustomView('::config', $this->name() . '::config');
        View::registerCustomView('::capsule', $this->name() . '::capsule');
        View::registerCustomView('::navbar', $this->name() . '::navbar');
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


    /**
     * Count the number of individuals in each tree.
     *
     * @return Collection<int,int>
     */
    private function totalIndividuals(): Collection
    {
        return DB::table('gedcom')
            ->leftJoin('individuals', 'i_file', '=', 'gedcom_id')
            ->groupBy(['gedcom_id'])
            ->pluck(new Expression('COUNT(i_id) AS aggregate'), 'gedcom_id')
            ->map(static fn (string $count): int => (int) $count);
    }

    /**
     * Count the number of families in each tree.
     *
     * @return Collection<int,int>
     */
    private function totalFamilies(): Collection
    {
        return DB::table('gedcom')
            ->leftJoin('families', 'f_file', '=', 'gedcom_id')
            ->groupBy(['gedcom_id'])
            ->pluck(new Expression('COUNT(f_id) AS aggregate'), 'gedcom_id')
            ->map(static fn (string $count): int => (int) $count);
    }

    /**
     * Count the number of sources in each tree.
     *
     * @return Collection<int,int>
     */
private function totalEvents(): array  
{  
    return DB::table('dates')    
        ->select('d_file as gedcom_id', DB::raw('COUNT(*) as count'))  
        ->whereNotIn('d_fact', ['HEAD', 'CHAN'])    
        ->groupBy('d_file')    
        ->pluck('count', 'gedcom_id') 
        ->all();  
}

    /**
     * Count the number of suenames in each tree.
     *
     * @return Collection<int,int>
     */
    private function totalSurnames(): Collection
    {
        return DB::table('gedcom')
            ->leftJoin('name', 'n_file', '=', 'gedcom_id')
            ->groupBy(['gedcom_id'])
            ->pluck(new Expression('COUNT(DISTINCT n_surn) AS aggregate'), 'gedcom_id')
            ->map(static fn (string $count): int => (int) $count);
    }

    public function getBlock(Tree $tree, int $block_id, string $context, array $config = []): string
    {
        $infoStyle = $this->getBlockSetting($block_id, 'infoStyle', self::DEFAULT_STYLE);
        $sortStyle = $this->getBlockSetting($block_id, 'sortStyle', self::DEFAULT_SORT);
        $sortedTrees = $this->tree_service->all(); // 假设返回的是一个Collection  
        if ($sortStyle === 'id_desc') {  
            $sortedTrees = $sortedTrees->sortByDesc(function ($sortedTrees) {  
            return $sortedTrees->id(); // 假设 $tree->id() 是获取 ID 的公共方法  
            })->all();  
        } 
                $content = view($infoStyle, [
                    'block_id' =>$block_id,
                    'all_trees'  => $sortedTrees,
                    'individuals'=> $this->totalIndividuals(),
                    'families'   => $this->totalFamilies(),
                    'events'    => $this->totalEvents(),
                    'surnames'  => $this->totalSurnames(),
                    'treeicon' => $this->assetUrl('images/tree.png'),
                    'familyicon' => $this->assetUrl('images/families.png'),
		    'individualicon' => $this->assetUrl('images/person2.png'),
		    'eventicon' => $this->assetUrl('images/event.png'),
		    'surnameicon' => $this->assetUrl('images/sur.png'),
		    'context' => $context,
                ]);

        if ($context !== self::CONTEXT_EMBED) {
            $totaltrees=$this->tree_service->all()->count();
       	    $title=I18N::plural('There is one tree on this website',  'This website has %d trees',$totaltrees, $totaltrees);
            return view('modules/block-template', [
                'block'      => Str::kebab($this->name()),
                'id'         => $block_id,
				'config_url' => $this->configUrl($tree, $context, $block_id),
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
        return '1.3.3';
    }


    /**
     * Update the configuration for a block.
     *
     * @param ServerRequestInterface $request
     * @param int                    $block_id
     *
     * @return void
     */
    public function saveBlockConfiguration(ServerRequestInterface $request, int $block_id): void
    {
        $info_style = Validator::parsedBody($request)->string('infoStyle');   // 显示风格
        $sort_style = Validator::parsedBody($request)->string('sortStyle');   // 排序，gedcom_id


        $this->setBlockSetting($block_id, 'infoStyle', $info_style);
        $this->setBlockSetting($block_id, 'sortStyle', $sort_style);
    }

    /**
     * An HTML form to edit block settings
     *
     * @param Tree $tree
     * @param int  $block_id
     *
     * @return string
     */
    public function editBlockConfiguration(Tree $tree, int $block_id): string
    {

        $info_style = $this->getBlockSetting($block_id, 'infoStyle', self::DEFAULT_STYLE);
        $sort_style = $this->getBlockSetting($block_id, 'sortStyle', self::DEFAULT_SORT);

        $info_styles = [
            'list'  => I18N::translate('list'),
            'table' => I18N::translate('table'),
            'card' => I18N::translate('card'),
            'capsule'=> I18N::translate('capsule'),
            'navbar'=> I18N::translate('navbar'),
        ];

        $sort_styles = [
            /* I18N: An option in a list-box */
            'id_asc'  => I18N::translate('sort by Gedcom_ID, oldest first'),
            /* I18N: An option in a list-box */
            'id_desc' => I18N::translate('sort by Gedcom_ID, newest first'),
        ];

        return view('config', [
            'info_style'  => $info_style,
            'info_styles' => $info_styles,
            'sort_style'  => $sort_style,
            'sort_styles' => $sort_styles,
        ]);
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
            'There is one tree on this website'. I18N::PLURAL .'This website has %d trees' => 'Es gibt einen Baum auf dieser Website'. I18N::PLURAL .'Diese Website hat %d Stammbäume',
            'FamilyTree List' => 'Stammbaum-Liste',
            'List of FamilyTree on Website' => 'Liste von FamilyTree auf der Website',
            'navbar' => 'Navbar',
            'card'=>'Karte',
            'capsule'=>'Kapsel',
            'sort by Gedcom_ID, oldest first'=>'Sortieren nach Gedcom-ID, älteste zuerst',
            'sort by Gedcom_ID, newest first'=>'Sortieren nach Gedcom-ID, neueste zuerst',
            '*Click on the header to sort the values.'=>'*Klicken Sie auf die Kopfzeile, um die Werte zu sortieren.',
        ];
    }

    protected function hansTranslations() : array
    {
        // 
        return [
            'There is one tree on this website'. I18N::PLURAL .'This website has %d trees' => '本网站已收录%d部家谱',
            'FamilyTree List' => '家谱列表',
            'List of FamilyTree on Website' => '显示网站上的家谱列表',
            'list'=>'列  表',
            'table'=>'表  格',
            'card'=>'卡  片',
            'capsule'=>'胶  囊',
            'navbar' => '导航栏',
            'sort by Gedcom_ID, oldest first'=>'按Gedcom_ID排序，正序',
            'sort by Gedcom_ID, newest first'=>'按Gedcom_ID排序，倒序',
            '*Click on the header to sort the values.'=>'*点击表头可对数值进行排序。',
        ];
    }
    
    protected function hantTranslations() : array
    {
        
        return [
            'There is one tree on this website'. I18N::PLURAL .'This website has %d trees' => '本網站已收錄%d部家譜',
            'FamilyTree List' => '家譜列表',
            'List of FamilyTree on Website' => '顯示網站上的家譜列表',
            'list'=>'列  表',
            'table'=>'表  格',
            'card'=>'卡  片',
            'capsule'=>'膠  囊',
            'navbar' => '导航栏',
            'sort by Gedcom_ID, oldest first'=>'按Gedcom_ID排序，最老優先',
            'sort by Gedcom_ID, newest first'=>'按Gedcom_ID排序，最新優先',
            '*Click on the header to sort the values.'=>'*點擊表頭可對數值進行排序。',
        ];
    }

}
