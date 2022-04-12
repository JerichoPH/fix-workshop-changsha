<?php

namespace App\Console\Commands;

use App\Facades\EntireInstanceFacade;
use App\Facades\TextFacade;
use App\Model\Account;
use App\Model\EntireInstance;
use App\Model\EntireInstanceCount;
use App\Model\EntireModel;
use App\Model\Factory;
use App\Model\PartCategory;
use App\Model\WorkArea;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

use Jericho\Model\Log;
use function GuzzleHttp\Promise\each;

class InitDataCommand extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'init:data {function_name}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    private $_functions = [];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 供应商
     */
    private function f1()
    {
        $factories = [
            '中国铁道科学研究院' => 'P0001',
            '北京全路通信信号研究设计院集团有限公司' => 'P0002',
            '北京市华铁信息技术开发总公司' => 'P0003',
            '通号(北京)轨道工业集团有限公司' => 'P0004',
            '河南辉煌科技股份有限公司' => 'P0005',
            '上海铁大电信科技股份有限公司' => 'P0006',
            '卡斯柯信号有限公司' => 'P0007',
            '北京和利时系统工程有限公司' => 'P0008',
            '西门子股份公司' => 'P0009',
            '沈阳铁路信号有限责任公司' => 'P0011',
            '北京安达路通铁路信号技术有限公司' => 'P0014',
            '北京安润通电子技术开发有限公司' => 'P0015',
            '北京北信丰元铁路电子设备有限公司' => 'P0016',
            '北京国铁路阳技术有限公司' => 'P0017',
            '北京交大微联科技有限公司' => 'P0018',
            '北京交通铁路技术研究所有限公司' => 'P0019',
            '北京津宇嘉信科技股份有限公司' => 'P0020',
            '北京全路通铁路专用器材工厂' => 'P0021',
            '北京全路通信信号研究设计院有限公司' => 'P0022',
            '北京铁路局太原电务器材厂' => 'P0023',
            '北京铁路信号有限公司' => 'P0024',
            '成都铁路通信仪器仪表厂' => 'P0025',
            '大连电机厂' => 'P0026',
            '丹东中天照明电器有限公司' => 'P0027',
            '固安北信铁路信号有限公司' => 'P0028',
            '固安通号铁路器材有限公司' => 'P0029',
            '固安信通信号技术股份有限公司' => 'P0030',
            '哈尔滨复盛铁路工电器材有限公司' => 'P0031',
            '哈尔滨铁晶铁路通信信号器材厂' => 'P0032',
            '哈尔滨铁路局直属机关通信信号器材厂' => 'P0033',
            '合肥市中铁电务有限责任公司' => 'P0034',
            '河北德凯铁路信号器材有限公司' => 'P0035',
            '河北冀胜轨道科技股份有限公司' => 'P0036',
            '河北南皮铁路器材有限责任公司' => 'P0037',
            '黑龙江瑞兴科技股份有限公司' => 'P0038',
            '济南三鼎电气有限责任公司' => 'P0039',
            '锦州长青铁路器材厂' => 'P0040',
            '洛阳通号铁路器材有限公司' => 'P0041',
            '南昌铁路电务设备厂' => 'P0042',
            '宁波鸿钢铁路信号设备厂' => 'P0043',
            '饶阳铁建电务器材有限公司' => 'P0044',
            '厦门科华恒盛股份有限公司' => 'P0045',
            '山特电子(深圳）有限公司' => 'P0046',
            '陕西赛福特铁路器材有限公司' => 'P0047',
            '陕西省咸阳市国营七九五厂' => 'P0048',
            '上海电捷工贸有限公司' => 'P0049',
            '上海铁路通信有限公司' => 'P0050',
            '上海铁路信号器材有限公司' => 'P0051',
            '深圳科安达电子科技股份有限公司' => 'P0052',
            '深圳市恒毅兴实业有限公司' => 'P0053',
            '深圳市铁创科技发展有限公司' => 'P0054',
            '沈阳宏达电机制造有限公司' => 'P0055',
            '沈阳铁路器材厂' => 'P0056',
            '四川浩铁仪器仪表有限公司' => 'P0057',
            '天津赛德电气设备有限公司' => 'P0058',
            '天津铁路信号有限责任公司' => 'P0059',
            '天水佳信铁路电气有限公司' => 'P0060',
            '天水铁路器材厂' => 'P0061',
            '天水铁路信号工厂' => 'P0062',
            '温州凯信电气有限公司' => 'P0063',
            '西安嘉信铁路器材有限公司' => 'P0064',
            '西安全路通号器材研究有限公司' => 'P0065',
            '西安铁路信号有限责任公司' => 'P0066',
            '西安无线电二厂' => 'P0067',
            '西安信通博瑞特铁路信号有限公司' => 'P0068',
            '西安宇通铁路器材有限公司' => 'P0069',
            '西安中凯铁路电气有限责任公司' => 'P0070',
            '偃师市泰达电务设备有限公司' => 'P0071',
            '浙江金华铁路信号器材有限公司' => 'P0072',
            '郑州世创电子科技有限公司' => 'P0073',
            '中国铁路通信信号集团有限公司' => 'P0074',
            'CSEE公司' => 'P0075',
            'GE公司' => 'P0076',
            '艾佩斯电力设施有限公司' => 'P0077',
            '安萨尔多' => 'P0078',
            '北京阿尔卡特' => 'P0079',
            '北京从兴科技有限公司' => 'P0080',
            '北京电务器材厂' => 'P0081',
            '北京国正信安系统控制技术有限公司' => 'P0082',
            '北京黄土坡信号厂' => 'P0083',
            '北京锦鸿希电信息技术股份有限公司' => 'P0084',
            '北京联能科技有限公司' => 'P0085',
            '北京联泰信科铁路信通技术有限公司' => 'P0086',
            '北京全路通号器材研究有限公司' => 'P0087',
            '北京世纪东方国铁科技股份有限公司' => 'P0088',
            '北京铁路分局西直门电务段' => 'P0089',
            '北京铁通康达铁路通信信号设备有限公司' => 'P0090',
            '北京兆唐有限公司' => 'P0091',
            '长沙南车电气设备有限公司' => 'P0092',
            '成都铁路通信设备有限责任公司' => 'P0093',
            '丹东东明铁路灯泡厂' => 'P0094',
            '奉化市皓盛铁路电务器材有限公司' => 'P0095',
            '广州华炜科技有限公司' => 'P0096',
            '广州铁路电务工厂' => 'P0097',
            '哈尔滨路通科技开发有限公司' => 'P0098',
            '哈尔滨市科佳通用机电有限公司' => 'P0099',
            '哈尔滨铁路通信信号器材厂' => 'P0100',
            '哈铁信号器材厂' => 'P0101',
            '杭州创联电子技术有限公司' => 'P0102',
            '鹤壁博大电子科技有限公司' => 'P0104',
            '湖南湘依铁路机车电器股份有限公司' => 'P0105',
            '兰州大成铁路信号有限责任公司' => 'P0106',
            '兰州铁路电务器材有限公司' => 'P0107',
            '柳州辰天科技有限责任公司' => 'P0108',
            '牡丹江电缆厂' => 'P0109',
            '南非断路器有限公司' => 'P0110',
            '南京电子管厂' => 'P0111',
            '南京圣明科技有限公司' => 'P0112',
            '宁波思高软件科技有限公司' => 'P0113',
            '齐齐哈尔电务器材厂' => 'P0114',
            '青岛四机易捷铁路器材有限公司' => 'P0115',
            '绕阳铁建电务器材有限公司' => 'P0116',
            '陕西众信铁路设备有限公司' => 'P0117',
            '上海电务工厂' => 'P0118',
            '上海瑞信电气有限公司' => 'P0119',
            '上海友邦电气股份有限公司' => 'P0120',
            '深圳长龙铁路电子工程有限公司' => 'P0121',
            '沈阳电务器材厂' => 'P0122',
            '施耐德电气信息技术（中国）有限公司' => 'P0123',
            '太原市京丰铁路电务器材制造有限公司' => 'P0124',
            '天水铁路电缆有限责任公司' => 'P0125',
            '天水铁路信号灯泡有限公司' => 'P0126',
            '万可电子（天津）有限公司' => 'P0127',
            '乌鲁木齐铁信公司' => 'P0128',
            '无锡同心铁路器材有限公司' => 'P0129',
            '西安大正信号有限公司' => 'P0130',
            '西安电务器材厂' => 'P0131',
            '西安东鑫瑞利德电子有限责任公司' => 'P0132',
            '西安开源仪表研究所' => 'P0133',
            '西安凯士信控制显示技术有限公司' => 'P0134',
            '西安天元铁路器材责任有限公司' => 'P0135',
            '西安通达电务器材厂' => 'P0136',
            '西安西电光电缆有限公司' => 'P0137',
            '西安西门子信号有限公司' => 'P0138',
            '西安信达铁路专用器材开发有限公司' => 'P0139',
            '襄樊电务器材厂' => 'P0140',
            '新铁德奥道岔有限公司' => 'P0141',
            '扬中市新华电务配件厂' => 'P0142',
            '郑州二七科达铁路器材厂' => 'P0143',
            '郑州华容电器科技有限公司' => 'P0144',
            '郑州铁路通号电务器材厂' => 'P0145',
            '中国铁道科学研究院通信信号研究所' => 'P0146',
            '重庆森威电子有限公司' => '	P0147',
            '株洲南车时代电气股份有限公司' => 'P0148',
            '北京怡蔚丰达电子技术有限公司' => 'P0149',
            '常州东方铁路器材有限公司' => 'P0150',
            'ABB（中国）有限公司' => 'P0151',
            '美国电力转换公司(APC）' => 'P0152',
            '施耐德' => 'P0153',
            'PORYAN' => 'P0154',
            '西安持信铁路器材有限公司' => 'P0155',
            '埃伯斯电子（上海）有限公司' => 'P0156',
            'Emerson(美国艾默生电气公司）' => 'P0157',
            '广东保顺能源股份有限公司' => 'P0158',
            '宝胜科技创新股份有限公司' => 'P0159',
            '北方交通大学信号抗干扰实验站' => 'P0160',
            '北京安英特技术开发公司' => 'P0161',
            '沧州铁路信号厂' => 'P0162',
            '北京大地同丰科技有限公司' => 'P0163',
            '北京丰台铁路电器元件厂' => 'P0164',
            '北京冠九州铁路器材有限公司' => 'P0165',
            '北京市交大路通科技有限公司' => 'P0167',
            '北京康迪森交通控制技术有限责任公司' => 'P0168',
            '北京六联信息技术研究所' => 'P0169',
            '北京施维格科技有限公司' => 'P0170',
            '北京世纪瑞尔技术股份有限公司' => 'P0171',
            '北京市丰台铁路电气元件厂' => 'P0172',
            '北京泰雷兹交通自动化控制系统有限公司' => 'P0173',
            '铁科院(北京)工程咨询有限公司' => 'P0174',
            '北京西南交大盛阳科技有限公司' => 'P0175',
            '朝阳电源有限公司' => 'P0176',
            '戴尔（Dell）' => 'P0177',
            '丹东铁路通达保安器件有限公司' => 'P0178',
            '德州津铁物资有限公司' => 'P0179',
            '天水长信铁路信号设备有限公司' => 'P0180',
            '天水铁路信号电缆厂' => 'P0181',
            '广州舰铭铁路设备有限公司' => 'P0182',
            '广州铁路（集团）公司电务工厂' => 'P0183',
            '通号通信信息集团有限公司广州分公司' => 'P0184',
            '广州忘平信息科技有限公司' => 'P0185',
            '杭州慧景科技股份有限公司' => 'P0186',
            '合肥中交电气有限公司' => 'P0187',
            '鹤壁市华研电子科技有限公司' => 'P0188',
            '湖北洪乐电缆股份有限公司' => 'P0189',
            '湖南中车时代通信信号有限公司' => 'P0190',
            '华为技术股份有限公司' => 'P0191',
            '惠普（HP）' => 'P0192',
            '济南瑞通铁路电务有限责任公司' => 'P0193',
            '江苏亨通电力电缆有限公司' => 'P0194',
            '江苏今创安达交通信息技术公司' => 'P0195',
            '焦作铁路电缆有限责任公司' => 'P0196',
            '上海良信电器股份有限公司' => 'P0197',
            '凌华科技(中国)有限公司' => 'P0198',
            '庞巴迪公司（Bombardier Inc.）' => 'P0199',
            '日本京三(KYOSAN)' => 'P0200',
            '瑞网数据通信设备（北京）有限公司' => 'P0201',
            '山西润泽丰科技开发有限公司' => 'P0202',
            '陕西通号铁路器材有限公司' => 'P0203',
            '西北铁道电子股份有限公司' => 'P0204',
            '上海德意达电子电器设备有限公司' => 'P0205',
            '上海慧轩电气科技有限公司' => 'P0206',
            '上海新干通通信设备有限公司' => 'P0207',
            '金华铁路通信信号器材厂' => 'P0208',
            '苏州飞利浦消费电子有限公司' => 'P0209',
            '武汉瑞控电气工程有限公司' => 'P0210',
            '天津七一二通信广播有限公司' => 'P0211',
            '天津海斯特电机有限公司' => 'P0212',
            '天津精达铁路器材有限公司' => 'P0213',
            '天水广信铁路信号公司' => 'P0214',
            '武汉贝通科技有限公司' => 'P0215',
            '西安盛达铁路电器有限公司' => 'P0216',
            '西安铁通科技开发实业公司' => 'P0217',
            '西安唯迅监控设备有限公司' => 'P0218',
            '西安铁路信号工厂' => 'P0219',
            '西安一信铁路器材有限公司' => 'P0220',
            '研华科技股份有限公司' => 'P0221',
            '扬州长城铁路器材有限公司' => 'P0222',
            '英沃思科技(北京)有限公司' => 'P0223',
            '宁波市皓盛铁路电务器材有限公司' => 'P0224',
            '郑州铁路专用器材有限公司' => 'P0225',
            '中车株洲电力机车研究所有限公司' => 'P0226',
            '中达电通股份有限公司' => 'P0227',
            '中国铁路通信信号股份有限公司(中国通号CRSC)' => 'P0228',
            '中利科技集团股份有限公司' => 'P0229',
            '中兴通讯股份有限公司' => 'P0230',
            '株洲中车时代电气股份有限公司' => 'P0231',
            'COMLAB（北京）通信系统设备有限公司' => 'P0232',
            '北京博飞电子技术有限责任公司' => 'P0233',
            '北京鼎汉技术集团股份有限公司' => 'P0235',
            '北京华铁信息技术有限公司' => 'P0236',
            '北京交大思诺科技股份有限公司' => 'P0237',
            '北京全路通信信号研究设计院集团有限公司广州分公司' => 'P0238',
            '北京信达环宇安全网络技术有限公司' => 'P0239',
            '北京英诺威尔科技股份有限公司' => 'P0240',
            '北京智讯天成技术有限公司' => 'P0241',
            '北京中智润邦科技有限公司' => 'P0242',
            '长沙飞波通信技术有限公司' => 'P0243',
            '长沙斯耐沃机电有限公司' => 'P0244',
            '长沙铁路建设有限公司' => 'P0245',
            '长沙智创机电设备有限公司' => 'P0246',
            '郴州长治建筑有限公司' => 'P0247',
            '楚天龙股份有限公司' => 'P0248',
            '东方腾大工程维修服务有限公司' => 'P0249',
            '高新兴创联科技有限公司' => 'P0250',
            '广东省肇庆市燊荣建筑安装装饰工程有限公司' => 'P0251',
            '广东永达建筑有限公司' => 'P0252',
            '广宁县第二建筑工程有限公司' => 'P0253',
            '广州里程通信设备有限公司' => 'P0254',
            '广州赛力迪软件科技有限公司' => 'P0255',
            '广州盛佳建业科技有限责任公司' => 'P0256',
            '广州市大周电子科技有限公司' => 'P0257',
            '广州市广源电子科技有限公司' => 'P0258',
            '广州昊明通信设备有限公司' => 'P0259',
            '海口思宏电子工程有限公司' => 'P0260',
            '海南国鑫实业有限公司' => 'P0261',
            '海南海岸网络科技有限公司' => 'P0262',
            '海南海口建筑集团有限公司' => 'P0263',
            '海南华联安视智能工程有限公司' => 'P0264',
            '海南建祥瑞建筑工程有限公司' => 'P0265',
            '海南中弘建设工程有限公司' => 'P0266',
            '海南寰宇华强网络科技有限公司' => 'P0267',
            '海南鑫泰隆水电工程有限公司' => 'P0268',
            '杭州慧景科技有限公司' => 'P0269',
            '河南蓝信科技有限责任公司' => 'P0270',
            '河南思维自动化设备股份有限公司' => 'P0271',
            '湖南长铁装备制造有限公司' => 'P0272',
            '湖南飞波工程有限公司' => 'P0273',
            '湖南省石柱建筑工程有限公司' => 'P0274',
            '湖南中车时代通信信号有限公司（株洲中车时代电气股份有限公司）' => 'P0275',
            '怀化铁路工程有限公司' => 'P0276',
            '怀化铁路工程总公司' => 'P0277',
            '江苏理士电池有限公司' => 'P0278',
            '江苏万华通信科技有限公司' => 'P0279',
            '南京盛佳建业科技有限责任公司' => 'P0280',
            '南京泰通科技股份有限公司' => 'P0281',
            '宁津南铁重工设备有限公司' => 'P0282',
            '饶阳县路胜铁路信号器材有限公司' => 'P0283',
            '陕西西北铁道电子股份有限公司' => 'P0284',
            '上海仁昊电子科技有限公司' => 'P0285',
            '深圳市速普瑞科技有限公司' => 'P0286',
            '深圳市英维克科技有限公司' => 'P0287',
            '通号（长沙）轨道交通控制技术有限公司' => 'P0288',
            '通号工程局集团有限公司' => 'P0289',
            '维谛技术有限公司' => 'P0290',
            '武汉佳和电气有限公司' => 'P0291',
            '西安博优铁路机电有限责任公司' => 'P0292',
            '浙江友诚铁路设备科技有限公司' => 'P0293',
            '中国海底电缆建设有限公司' => 'P0294',
            '中国铁道科学研究院集团有限公司通信信号研究所' => 'P0295',
            '中国铁建电气化局集团有限公司' => 'P0296',
            '中国铁路通信信号股份有限公司' => 'P0297',
            '中国铁路通信信号上海工程局集团有限公司' => 'P0298',
            '中山市德全建设工程有限公司' => 'P0299',
            '中铁电气化局第一工程有限公司' => 'P0300',
            '中铁电气化局集团第三工程有限公司' => 'P0301',
            '中铁电气化局集团有限公司' => 'P0302',
            '中铁二十五局集团电务工程有限公司' => 'P0303',
            '中铁建电气化局集团第四工程有限公司' => 'P0304',
            '中铁四局集团电气化工程有限公司' => 'P0305',
            '中铁武汉电气化局集团第一工程有限公司' => 'P0306',
            '中铁武汉电气化局集团有限公司' => 'P0308',
            '中移建设有限公司' => 'P0309',
            '珠海朗电气有限公司' => 'P0310',
            '株洲市亿辉贸易有限公司' => 'P0311',
            '湖南长铁工业开发有限公司' => 'P0312',
            '大连嘉诺机械制造有限公司' => 'P0313',
            '天津宝力电源有限公司' => 'P0314',
            '广东广特电气股份有限公司' => 'P0315',
            '沈阳希尔科技发展有限公司' => 'P0316',
        ];
        DB::table('factories')->truncate();
        foreach ($factories as $factory_name => $factory_unique_code) {
            $factory = Factory::with([])->create([
                'name' => $factory_name,
                'unique_code' => $factory_unique_code
            ]);
            $this->info($factory->name, $factory->unique_code);
        }

        // $change_factories = [
        //     '北京交大斯诺科技公司' => '北京交大思诺科技股份有限公司',
        //     '北京鼎汉技术有限公司' => '北京鼎汉技术集团股份有限公司',
        //     '河南蓝信科技股份有限公司' => '河南蓝信科技有限责任公司',
        //     '北京局太原电务器材厂' => '北京铁路局太原电务器材厂',
        //     '太原电务器材厂' => '北京铁路局太原电务器材厂',
        //     '广州电务工厂' => '广州铁路（集团）公司电务工厂',
        //     '济南三鼎' => '济南三鼎电气有限责任公司',
        //     '青岛四机' => '青岛四机易捷铁路器材有限公司',
        //     '北京铁路信号工厂' => '北京铁路信号有限公司',
        //     '天津信号工厂' => '天津铁路信号有限责任公司',
        //     '沈阳信号工厂' => '沈阳铁路信号有限责任公司',
        // ];
        //
        // foreach ($change_factories as $old => $new) {
        //     EntireInstance::with([])->where('factory_name', $old)->update(['factory_name' => $new]);
        //     $this->info("{$old} => {$new}");
        // }
    }

    /**
     * 工区
     */
    private function f2()
    {
        DB::table('work_areas')->truncate();
        DB::statement("alter table work_areas modify type enum('', 'pointSwitch', 'relay', 'synthesize', 'scene', 'powerSupplyPanel1') default '' not null comment 'pointSwitch：转辙机工区
reply：继电器工区
synthesize：综合工区
scene：现场工区
powerSupplyPanel：电源屏工区'");

        $this->info("工区 => 人员 开始");
        $origin_time = time();
        $now = now();
        $workshop_unique_code = env('ORGANIZATION_LOCATION_CODE');
        $paragraph_unique_code = env('ORGANIZATION_CODE');
        switch (env('ORGANIZATION_CODE')) {
            case 'B049':
                // 长沙没有电源屏工区
                $work_areas = [
                    [
                        'created_at' => $now,
                        'updated_at' => $now,
                        'workshop_unique_code' => $workshop_unique_code,
                        'unique_code' => "{$paragraph_unique_code}D01",
                        'name' => '转辙机工区',
                        'type' => 'pointSwitch',
                        'paragraph_unique_code' => $paragraph_unique_code,
                    ], [
                        'created_at' => $now,
                        'updated_at' => $now,
                        'workshop_unique_code' => $workshop_unique_code,
                        'unique_code' => "{$paragraph_unique_code}D02",
                        'name' => '继电器工区',
                        'type' => 'relay',
                        'paragraph_unique_code' => $paragraph_unique_code,
                    ], [
                        'created_at' => $now,
                        'updated_at' => $now,
                        'workshop_unique_code' => $workshop_unique_code,
                        'unique_code' => "{$paragraph_unique_code}D03",
                        'name' => '综合工区',
                        'type' => 'synthesize',
                        'paragraph_unique_code' => $paragraph_unique_code,
                    ],
                ];
                break;
            default:
                $work_areas = [
                    [
                        'created_at' => $now,
                        'updated_at' => $now,
                        'workshop_unique_code' => $workshop_unique_code,
                        'unique_code' => "{$paragraph_unique_code}D01",
                        'name' => '转辙机工区',
                        'type' => 'pointSwitch',
                        'paragraph_unique_code' => $paragraph_unique_code,
                    ], [
                        'created_at' => $now,
                        'updated_at' => $now,
                        'workshop_unique_code' => $workshop_unique_code,
                        'unique_code' => "{$paragraph_unique_code}D02",
                        'name' => '继电器工区',
                        'type' => 'relay',
                        'paragraph_unique_code' => $paragraph_unique_code,
                    ], [
                        'created_at' => $now,
                        'updated_at' => $now,
                        'workshop_unique_code' => $workshop_unique_code,
                        'unique_code' => "{$paragraph_unique_code}D03",
                        'name' => '综合工区',
                        'type' => 'synthesize',
                        'paragraph_unique_code' => $paragraph_unique_code,
                    ], [
                        'created_at' => $now,
                        'updated_at' => $now,
                        'workshop_unique_code' => $workshop_unique_code,
                        'unique_code' => "{$paragraph_unique_code}D03",
                        'name' => '电源屏工区',
                        'type' => 'synthesize',
                        'paragraph_unique_code' => $paragraph_unique_code,
                    ],
                ];
                break;
        }
        foreach ($work_areas as $work_area) {
            if (!WorkArea::with([])->where('unique_code', $work_area['unique_code'])->exists()) {
                WorkArea::with([])->create($work_area);
            }
        }

        // 人员所属工区刷库
        $work_areas = [
            '无' => '',
            '转辙机工区' => "{$paragraph_unique_code}D01",
            '继电器工区' => "{$paragraph_unique_code}D02",
            '综合工区' => "{$paragraph_unique_code}D03",
            '电源屏工区' => "{$paragraph_unique_code}D04",
        ];
        Account::with([])->each(function (Account $account) use ($work_areas) {
            $account->fill([
                'workshop_unique_code' => env('ORGANIZATION_CODE'),
                'work_area_unique_code' => $work_areas[$account->work_area]
            ])->saveOrFail();
        });
        $used_time = ceil(time() - $origin_time);
        $this->info("工区 => 人员 结束：{$used_time}秒");

        // 设备刷库 todo: 设备所属工区刷库
        EntireInstance::with([])->where('category_unique_code', 'S03')->update(['work_area_unique_code' => "{$paragraph_unique_code}D01"]);
        EntireInstance::with([])->where('category_unique_code', 'Q01')->update(['work_area_unique_code' => "{$paragraph_unique_code}D02"]);
        switch (env('ORGANIZATION_CODE')) {
            case 'B049':
                // 长沙段没有电源屏工区
                EntireInstance::with([])->whereNotIn('category_unique_code', ['S03', 'Q01'])->update(['work_area_unique_code' => "{$paragraph_unique_code}D03"]);
                break;
            default:
                // 如果是其他段，则Q07归到电源屏工区
                EntireInstance::with([])->whereNotIn('category_unique_code', ['S03', 'Q01', 'Q07'])->update(['work_area_unique_code' => "{$paragraph_unique_code}D03"]);
                EntireInstance::with([])->where('category_unique_code', 'Q07')->update(['work_area_unique_code' => "{$paragraph_unique_code}D04"]);
                break;
        }
        $used_time = ceil(time() - $origin_time);
        $this->info("工区 => 设备 结束：{$used_time}秒");
    }

    /**
     * 部件型号
     */
    private function f3()
    {
        $origin_time = time();
        $this->info('f3 开始');
        // 纠正错误名称
        EntireModel::with([])->where('unique_code', 'Q0403')->update(['name' => '移位接触器']);
        EntireModel::with([])
            ->where('unique_code', 'Q0409')
            ->where('name', '油泵')
            ->where('is_sub_model', false)
            ->where('category_unique_code', 'Q04')
            ->firstOrCreate([
                'unique_code' => 'Q0409',
                'name' => '油泵',
                'category_unique_code' => 'Q04',
                'is_sub_model' => false,
            ]);
        PartCategory::with([])->where('id', 5)->update(['name' => '自动开闭器']);
        PartCategory::with([])->where('category_unique_code', 'S03')->where('name', '摩擦连接器')->firstOrCreate([
            'category_unique_code' => 'S03',
            'name' => '摩擦连接器',
            'is_main' => false,
        ]);
        PartCategory::with([])->where('id', 1)->update(['entire_model_unique_code' => 'Q0405']);
        PartCategory::with([])->where('id', 2)->update(['entire_model_unique_code' => 'Q0403']);
        PartCategory::with([])->where('id', 3)->update(['entire_model_unique_code' => 'Q0406']);
        PartCategory::with([])->where('id', 4)->update(['entire_model_unique_code' => 'Q0409']);
        PartCategory::with([])->where('id', 5)->update(['entire_model_unique_code' => 'Q0401']);
        PartCategory::with([])->where('id', 6)->update(['entire_model_unique_code' => 'Q0402']);

        // 加入电机型号
        $dianji_models = [
            'ZD6-A', 'ZD6-B', 'ZD6-D', 'ZD6-E', 'ZD6-F', 'ZD6-G', 'ZD6-H', 'ZD6-J', 'ZDG-III',
            'ZD9', 'ZD9-A', 'ZD9-B', 'ZD9-C', 'ZD9-D', 'ZD(J)9', 'ZY-4', 'ZY-6', 'ZY-7', 'ZYJ-2', 'ZYJ-3', 'ZYJ-4', 'ZYJ-5',
            'ZYJ-6', 'ZYJ7', 'ZYJ7-A', 'ZYJ7-J', 'ZYJ7-K', 'S700K-A10', 'S700K-A13', 'S700K-A14', 'S700K-A15', 'S700K-A16',
            'S700K-A17', 'S700K-A18', 'S700K-A19', 'S700K-A20', 'S700K-A21', 'S700K-A22',
            'S700K-A29', 'S700K-A30', 'S700K-A33', 'ZK-3A', 'ZK-4', 'ZD7-A', 'ZD7-C', 'S700K',
            'ZD6-K', 'S700K-A27', 'S700K-A28', 'WB', 'SBQ', 'BSQ',
        ];
        foreach ($dianji_models as $item) {
            $entire_model = EntireModel::with([])->where('parent_unique_code', 'Q0405')->where('is_sub_model', true)->orderByDesc('id')->first();
            $max_unique_code = $entire_model ? substr($entire_model->unique_code, 5, 3) : '00';
            $max_unique_code = TextFacade::from36($max_unique_code);
            $new_unique_code = 'Q0405' . str_pad(TextFacade::to36(++$max_unique_code), 2, '0', 0);
            $new_entire_model = EntireModel::with([])->create([
                'name' => $item,
                'unique_code' => $new_unique_code,
                'category_unique_code' => 'Q04',
                'fix_cycle_unit' => 'YEAR',
                'fix_cycle_value' => 0,
                'is_sub_model' => true,
                'parent_unique_code' => 'Q0405',
            ]);
        }

        $used_time = time() - $origin_time;
        $this->info("f3 执行完毕，用时：{$used_time}");
    }

    /**
     * 子类改为36进制
     */
    private function f4()
    {
        $this->info("子类改为36进制 开始");
        $origin_time = time();
        $sub_models = EntireModel::with([])
            ->where('is_sub_model', true)
            ->get();

        foreach ($sub_models as $sub_model) {
            if (strlen($sub_model->unique_code) <= 7) continue;
            $old_unique_code = $sub_model->unique_code;
            $first = substr($old_unique_code, 0, 5);
            $unique_code = substr($old_unique_code, 5);
            $new_unique_code = $first . str_pad(TextFacade::to36(intval($unique_code)), 2, '0', 0);
            $sub_model->fill(['unique_code' => $new_unique_code])->saveOrFail();
            DB::table('entire_instances as ei')->where('ei.model_unique_code', $old_unique_code)->update(['model_unique_code' => $new_unique_code, 'entire_model_unique_code' => $new_unique_code]);
        }
        $used_time = ceil(time() - $origin_time);
        $this->info("子类改为36进制 结束：{$used_time}秒");
    }

    /**
     * 计算设备总数
     */
    private function f5()
    {
        $this->info('更新设备总数开始');
        $origin_time = time();
        DB::beginTransaction();
        $entire_model_unique_codes = DB::table('entire_instances as ei')
            ->selectRaw('ei.entire_model_unique_code')
            ->groupBy(['ei.entire_model_unique_code'])
            ->pluck('ei.entire_model_unique_code')
            ->toArray();

        DB::table('entire_instance_counts')->truncate();
        foreach ($entire_model_unique_codes as $entire_model_unique_code) {
            $entire_instance = EntireInstance::with([])->select('identity_code')->where('entire_model_unique_code', $entire_model_unique_code)->orderByDesc('id')->first();
            if ($entire_instance) {
                $pos = strpos($entire_instance->identity_code, env('ORGANIZATION_CODE'));
                $max = intval(substr($entire_instance->identity_code, $pos + 4));
                EntireInstanceCount::with([])->where('entire_model_unique_code', $entire_model_unique_code)->updateOrCreate([
                    'entire_model_unique_code' => $entire_model_unique_code,
                    'count' => $max,
                ]);
                dump("{$entire_model_unique_code} => {$max}");
            }
        }
        DB::commit();
        $run_time = time() - $origin_time;
        $this->info("更新设备总是完成：{$run_time}秒");
    }

    /**
     * 刷新人员所属电务段
     */
    private function f6()
    {
        $this->info('人员所属段标识 开始');
        $origin_time = time();
        $ret = boolval(Account::with([])->update(['workshop_code' => env('ORGANIZATION_CODE')]));
        Account::with([])->where('account', 'admin')->update(['nickname' => '管理员(' . env('ORGANIZATION_NAME') . ')']);
        $used_time = ceil(time() - $origin_time);
        $this->info("人员所属段标识 结束：{$used_time}秒");
    }

    /**
     * 更新数据库，所有表，所有表子段的字符集和字符集排序
     */
    private function f7()
    {
        $db_name = env('DB_DATABASE');

        foreach (array_pluck(DB::select("SELECT TB.TABLE_NAME FROM INFORMATION_SCHEMA.TABLES TB WHERE TB.TABLE_SCHEMA = '{$db_name}'"), 'TABLE_NAME') as $table_name) {
            // 修改表默认字符集和排序
            $statement_result = DB::statement("alter table `{$table_name}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            // 修改字段默认字符集和排序
            // $column_names = array_pluck(DB::select("select COLUMN_NAME from INFORMATION_SCHEMA.Columns where table_name='$table_name' and table_schema='{$db_name}'"), 'COLUMN_NAME');

            dump("{$table_name}执行结果:{$statement_result}");
        }
    }

    /**
     * 刷库：entire_instance_counts表中不正确的数字(根据实际设备数字)
     */
    private function f8()
    {
        $this->info('刷新：entire_instance_counts 开始');

        DB::table('entire_instance_counts')->truncate();  // 清空表

        // 获取当前所有设备/器材已经导入的型号
        DB::table('entire_instances as ei')
            ->select(['ei.entire_model_unique_code'])
            ->groupBy(['ei.entire_model_unique_code'])
            ->orderByDesc('ei.identity_code')
            ->chunk(50, function (Collection $entire_instances) {
                $entire_instances->each(function ($entire_instance) {
                    $v = DB::table('entire_instances as ei')
                        ->select(['ei.identity_code', 'ei.entire_model_unique_code as eu'])
                        ->where('ei.entire_model_unique_code', $entire_instance->entire_model_unique_code)
                        ->orderByDesc('ei.identity_code')
                        ->first();
                    $last_code = substr($v->identity_code, (strlen($v->eu) + 4));
                    $last_code = intval(ltrim($last_code, 'H'));
                    $this->info("{$v->eu}:{$last_code}");
                    if ((EntireInstanceCount::with([])->where('entire_model_unique_code', $v->eu)->exists())) {
                        EntireInstanceCount::with([])->where('entire_model_unique_code', $v->eu)->update(['count' => $last_code]);
                    } else {
                        EntireInstanceCount::with([])->create(['entire_model_unique_code' => $v->eu, 'count' => $last_code]);
                    }
                    $this->info("{$v->eu}:{$last_code}");
                });
            });

        $this->info('刷新：entire_instance_counts 完成');
    }

    /**
     * 根据到期日期和生产日期倒推寿命
     */
    private function f9()
    {
        $this->info('根据到期日期和生产日期倒推寿命 开始');
        // 修改所有寿命为15
        EntireInstance::with([])->update(['life_year' => 15]);

        // 根据到期日期和生产日期倒推寿命
        EntireInstance::with([])
            ->where('made_at', '<>', null)
            ->where('scarping_at', '<>', null)
            ->chunk(50, function (Collection $entire_instances) {
                $entire_instances->each(function (EntireInstance $entire_instance) {
                    $made_at = Carbon::parse($entire_instance->made_at)->startOfYear();
                    $scarping_at = Carbon::parse($entire_instance->scarping_at)->endOfYear();
                    $life_year = $made_at->diffInYears($scarping_at, true);
                    $entire_instance->fill(['life_year' => $life_year])->saveOrFail();
                    $this->info("{$entire_instance->identity_code}:{$entire_instance->life_year}");
                });
            });

        $this->info('根据到期日期和生产日期倒推寿命 完成');
    }

    /**
     * 根据寿命规范下次周期修时间
     * @throws \Throwable
     */
    private function f10()
    {
        $this->info('重新计算周期修时间 开始');

        EntireInstance::with(['EntireModel'])
            ->chunk(50, function (Collection $entire_instances) {
                $entire_instances->each(function (EntireInstance $entire_instance) {
                    if (!($entire_instance->fix_cycle_value > 0 || $entire_instance->EntireModel->fix_cycle_value > 0)) {
                        $this->info("{$entire_instance->identity_code} 跳过。原因：不是周期修设备/器材");
                    } elseif (!($entire_instance->last_installed_time > 0 || $entire_instance->last_out_at)) {
                        $this->info("{$entire_instance->identity_code} 跳过。原因：没有出所或上道时间");
                    } else {
                        [
                            'next_auto_making_fix_workflow_time' => $next_auto_making_fix_workflow_time,
                            'next_fixing_time' => $next_fixing_time,
                            'next_auto_making_fix_workflow_at' => $next_auto_making_fix_workflow_at,
                            'next_fixing_month' => $next_fixing_month,
                            'next_fixing_day' => $next_fixing_day,
                        ] = EntireInstanceFacade::nextFixingTime($entire_instance);
                        if ($entire_instance->scarping_at) {
                            $scarping_at = Carbon::parse($entire_instance->scarping_at)->timestamp;
                            if ($next_fixing_time > $scarping_at) {
                                // 以报废日期为准
                                $next_fixing_time = $scarping_at;
                                $next_fixing_month = Carbon::createFromTimestamp($next_fixing_time)->startOfMonth()->format('Y-m-d');
                                $next_fixing_day = Carbon::createFromTimestamp($next_fixing_time)->format('Y-m-d');
                            }
                        }
                        $entire_instance
                            ->fill([
                                'next_fixing_time' => $next_fixing_time,
                                'next_fixing_month' => $next_fixing_month,
                                'next_fixing_day' => $next_fixing_day,
                            ])
                            ->saveOrFail();
                        $this->info("{$entire_instance->identity_code} 重新计算周期修时间：{$next_fixing_day}");
                    }
                });
            });

        $this->info('重新计算周期修时间 完成');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (!method_exists($this, $this->argument('function_name'))) {
            $this->error("错误，方法：{$this->argument('function_name')} 不存在。");
            return 0;
        }

        $this->{$this->argument('function_name')}();
        return 0;
    }
}
