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
    private function totalEvents(): Collection
    {
        return DB::table('gedcom')
            ->leftJoin('dates', 'd_file', '=', 'gedcom_id')
            ->groupBy(['gedcom_id'])
            ->pluck(new Expression('COUNT(d_file) AS aggregate'), 'gedcom_id')
            ->map(static fn (string $count): int => (int) $count);
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
    
        $content = view('trees-table', [
                    'block_id' =>$block_id,
                    'all_trees'  => $this->tree_service->all(),
                    'individuals'=> $this->totalIndividuals(),
                    'families'   => $this->totalFamilies(),
                    'events'    => $this->totalEvents(),
                    'surnames'  => $this->totalSurnames(),
                    'familyicon' => $this->assetUrl('images/families.png'),
			    	'individualicon' => $this->assetUrl('images/person1.png'),
			    	'eventicon' => $this->assetUrl('images/event.png'),
			    	'surnameicon' => $this->assetUrl('images/surn.png'),
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
        return '1.2.0';
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
