<?php
/**
 * cms配置开关
 */
return [
    //是否多域名
    'multi_domains' => env('MULTI_DOMAINS', false),

    //可选主题
    'themes'        => [
        'zaixianmeiju' => '在线美剧',
    ],
	'icp'			=> [
		'近邻乐' =>[
			'copyright' => 'Copyright ©2018-2021 近邻乐（深圳）有限责任公司 All Rights Reserved',
			'record_code' => '粤ICP备15053300号',
			'police_code' => '粤公网安备 44030302000783号',
			'police_code_number' => '44030302000783',
		],
		'深圳哈希坊' =>[
			'copyright' => 'Copyright ©2021 哈希坊（深圳）科技有限责任公司 All Rights Reserved',
			'record_code' => '粤ICP备19147077号',
			'police_code' => '粤公网安备 44030302001429号',
			'police_code_number' => '44030302001429',
		],
		'成都哈希坊' =>[
			'copyright' => 'Copyright ©2021 哈希坊（成都）科技有限责任公司 All Rights Reserved',
			'record_code' => '蜀ICP备20024461号',
			'police_code' => '蜀公网安备 34130302005623号',
			'police_code_number' => '34130302005623',
		],
		'衡阳哈希坊' =>[
			'copyright' => 'Copyright ©2021 衡阳哈希坊科技有限公司 All Rights Reserved',
			'record_code' => '湘ICP备19021653号',
		],
		'老哈希表' =>[
			'copyright' => 'Copyright ©2019 三河市哈希表计算机技术有限公司 All Rights Reserved',
			'record_code' => '冀ICP备17022765号',
			'police_code' => '冀安网安备 13108202000425号',
			'police_code_number' => '13108202000425',
		],
	]
];
