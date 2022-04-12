alter table work_areas modify type enum('', 'pointSwitch', 'relay', 'reply', 'synthesize', 'scene', 'powerSupplyPanel', 'emergency') default '' not null comment 'pointSwitch：转辙机工区
reply：继电器工区
synthesize：综合工区
scene：现场工区
powerSupplyPanel：电源屏工区
emergency：应急备品中心工区';

