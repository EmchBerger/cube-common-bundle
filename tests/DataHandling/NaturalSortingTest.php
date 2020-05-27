<?php

namespace Tests\CubeTools\CubeCommonBundle\DataHandling;

use CubeTools\CubeCommonBundle\DataHandling\NaturalSorting;

class NaturalSortingTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var array subsequent elements are all unique identifiers for test procedures
     */
    protected $testProceduresUnsorted = array(
        0 => 'UC37-TP1',
        1 => 'UC64-TP1',
        2 => 'UC37-TP2',
        3 => 'UC50-TP1',
        4 => 'UC50-TP2',
        5 => 'UC50-TP3',
        6 => 'UC64-TP2',
        7 => 'UC34-TP1',
        8 => 'UC61-TP1',
        9 => 'UC61-TP2',
        10 => 'UC61-TP3',
        11 => 'UC56-TP4',
        12 => 'UC56-TP5',
        13 => 'UC56-TP2',
        14 => 'UC56-TP3',
        15 => 'UC56-TP1',
        16 => 'UC52-TP1',
        17 => 'UC35-TP1',
        18 => 'UC35-TP5',
        19 => 'UC70-TP3',
        20 => 'UC70-TP1',
        21 => 'UC70-TP2',
        22 => 'UC34-TP5',
        23 => 'UC52-TP4',
        24 => 'UC52-TP6',
        25 => 'UC52-TP8',
        26 => 'UC52-TP2',
        27 => 'UC76-TP1',
        28 => 'UC76-TP5',
        29 => 'UC73-TP1',
        30 => 'UC76-TP6',
        31 => 'UC73-TP2',
        32 => 'UC73-TP3',
        33 => 'UC73-TP4',
        34 => 'UC76-TP7',
        35 => 'UC76-TP8',
        36 => 'UC101-TP1',
        37 => 'UC101-TP3',
        38 => 'UC66-TP1',
        39 => 'UC66-TP2',
        40 => 'UC61-TP3W',
        41 => 'UC64-TP1W',
        42 => 'UC66-TP3',
        43 => 'UC37-TP1W',
        44 => 'UC37-TP2W',
        45 => 'UC64-TP2W',
        46 => 'UC85-TP1',
        47 => 'UC85-TP2',
        48 => 'UC101-TP3W',
        49 => 'UC73-TP1W',
        50 => 'UC66-TP1W',
        51 => 'UC73-TP2W',
        52 => 'UC66-TP2W',
        53 => 'UC66-TP3W',
        54 => 'UC73-TP3W',
        55 => 'UC73-TP4W',
        56 => 'UC50-TP1W',
        57 => 'UC76-TP1W',
        58 => 'UC76-TP8W',
        59 => 'UC41-TP1W',
        60 => 'UC78-TP1W',
        61 => 'UC41-TP2W',
        62 => 'UC41-TP3W',
        63 => 'UC41-TP5W',
        64 => 'UC78-TP2W',
        65 => 'UC78-TP4W',
        66 => 'UC78-TP6W',
        67 => 'UC47-TP1W',
        68 => 'UC47-TP3W',
        69 => 'UC47-TP5W',
        70 => 'UC47-TP2W',
        71 => 'UC47-TP4W',
        72 => 'UC47-TP6W',
        73 => 'UC71-TP1W',
        74 => 'UC109-TP2W',
        75 => 'UC109-TP1W',
        76 => 'UC109-TP3W',
        77 => 'UC75-TP1W',
        78 => 'UC75-TP2W',
        79 => 'UC75-TP3W',
        80 => 'UC75-TP4W',
        81 => 'UC75-TP5W',
        82 => 'UC75-TP7W',
        83 => 'UC75-TP8W',
        84 => 'UC73-TP6W',
        85 => 'UC59-TP2W',
        86 => 'UC59-TP3W',
        87 => 'UC59-TP4W',
        88 => 'UC67-TP1W',
        89 => 'UC67-TP2W',
        90 => 'UC62-TP1W',
        91 => 'UC62-TP2W',
        92 => 'UC62-TP3W',
        93 => 'UC79-TP1W',
        94 => 'UC79-TP2W',
        95 => 'UC79-TP3W',
        96 => 'UC79-TP4W',
        97 => 'UC143-TP2W',
        98 => 'UC60-TP1W',
        99 => 'UC58-TP1W',
        100 => 'UC58-TP4W',
        101 => 'UC58-TP5W',
        102 => 'UC58-TP6W',
        103 => 'UC60-TP2W',
        104 => 'UC60-TP3W',
        105 => 'UC60-TP5',
        106 => 'UC92-TP1W',
        107 => 'UC92-TP2W',
        108 => 'UC86-TP1',
        109 => 'UC86-TP2',
        110 => 'UC41-TP1',
        111 => 'UC35-TP2',
        112 => 'UC35-TP3',
        113 => 'UC35-TP4',
        114 => 'UC34-TP2',
        115 => 'UC34-TP3',
        116 => 'UC34-TP4',
        117 => 'UC68-TP1',
        118 => 'UC41-TP2',
        119 => 'UC41-TP3',
        120 => 'UC41-TP5',
        121 => 'UC41-TP4',
        122 => 'UC60-TP1',
        123 => 'UC78-TP1',
        124 => 'UC78-TP2',
        125 => 'UC78-TP4',
        126 => 'UC60-TP2',
        127 => 'UC60-TP3',
        128 => 'UC60-TP5W',
        129 => 'UC119-TP4',
        130 => 'UC60-TP4',
        131 => 'UC119-TP1',
        132 => 'UC47-TP1',
        133 => 'UC119-TP2',
        134 => 'UC119-TP3',
        135 => 'UC47-TP2',
        136 => 'UC47-TP3',
        137 => 'UC73-TP6',
        138 => 'UC47-TP4',
        139 => 'UC47-TP5',
        140 => 'UC47-TP6',
        141 => 'UC78-TP6',
        142 => 'UC78-TP3',
        143 => 'UC78-TP5',
        144 => 'UC75-TP1',
        145 => 'UC75-TP3',
        146 => 'UC75-TP4',
        147 => 'UC75-TP5',
        148 => 'UC75-TP7',
        149 => 'UC75-TP8',
        150 => 'UC67-TP1',
        151 => 'UC67-TP2',
        152 => 'UC59-TP2',
        153 => 'UC59-TP3',
        154 => 'UC59-TP4',
        155 => 'UC79-TP2',
        156 => 'UC79-TP3',
        157 => 'UC79-TP4',
        158 => 'UC79-TP1',
        159 => 'UC92-TP1',
        160 => 'UC92-TP2',
        161 => 'UC62-TP1',
        162 => 'UC62-TP2',
        163 => 'UC62-TP3',
        164 => 'UC58-TP1',
        165 => 'UC58-TP4',
        166 => 'UC58-TP5',
        167 => 'UC58-TP6',
        168 => 'UC29-TP1',
        169 => 'UC30-TP1',
        170 => 'UC58-TP7',
        171 => 'UC30-TP2',
        172 => 'UC29-TP2',
        173 => 'UC29-TP3',
        174 => 'UC29-TP4',
        175 => 'UC52-TP3',
        176 => 'UC28-TP3',
        177 => 'UC52-TP7',
        178 => 'UC28-TP4',
        179 => 'UC52-TP5',
        180 => 'UC27-TP1',
        181 => 'UC31-TP1',
        182 => 'UC31-TP2',
        183 => 'UC32-TP1',
        184 => 'UC32-TP2',
        185 => 'UC32-TP3',
        186 => 'UC41-TP4W',
        187 => 'UC68-TP1W',
        188 => 'UC58-TP7W',
        189 => 'UC76-TP7W',
        190 => 'UC119-TP1W',
        191 => 'UC119-TP2W',
        192 => 'UC119-TP3W',
        193 => 'UC119-TP4W',
        194 => 'UC60-TP4W',
        195 => 'UC202-TP1',
        196 => 'UC202-TP3',
        197 => 'UC202-TP4',
        198 => 'UC202-TP2',
        199 => 'AF-BC1-TP1',
        200 => 'UC200-TP1',
        201 => 'UC200-TP2',
        202 => 'AF-BC6-TP1',
        203 => 'AF-BC21-TP1',
        204 => 'AF-BC48-TP1',
        205 => 'UC201-TP1',
        206 => 'UC201-TP2',
        207 => 'UC201-TP3',
        208 => 'UC201-TP4',
        209 => 'UC13-TP1',
        210 => 'UC13-TP2',
        211 => 'UC13-TP3',
        212 => 'UC13-TP4',
        213 => 'UC13-TP5',
        214 => 'UC109-TP1',
        215 => 'UC109-TP2',
        216 => 'UC109-TP3',
        217 => 'UC75-TP2',
        218 => 'UC49-TP1',
        219 => 'UC57-TP1',
        220 => 'UC107-TP1',
        221 => 'UC99-TP1',
        222 => 'UC99-TP2',
        223 => 'UC107-TP2',
        224 => 'UC53-TP1',
        225 => 'UC53-TP2',
        226 => 'UC75-TP6',
        227 => 'UC57-TP2',
        228 => 'UC82-TP1',
        229 => 'UC77-TP1',
        230 => 'UC108-TP1',
        231 => 'UC82-TP2',
        232 => 'UC108-TP2',
        233 => 'UC108-TP3',
        234 => 'UC108-TP4',
        235 => 'UC202-TP5',
        236 => 'UC202-TP6',
        237 => 'UC202-TP7',
        238 => 'UC202-TP8',
        239 => 'UC50-TP2W',
        240 => 'UC50-TP3W',
        241 => 'UC53-TP1W',
        242 => 'UC53-TP2W',
        243 => 'UC49-TP1W',
        244 => 'UC57-TP1W',
        245 => 'UC61-TP1W',
        246 => 'UC61-TP2W',
        247 => 'UC75-TP6W',
        248 => 'UC99-TP1W',
        249 => 'UC99-TP2W',
        250 => 'UC76-TP5W',
        251 => 'UC76-TP6W',
        252 => 'UC57-TP2W',
        253 => 'UC78-TP3W',
        254 => 'UC78-TP5W',
        255 => 'UC86-TP1W',
        256 => 'UC86-TP2W',
        257 => 'UC101-TP1W',
        258 => 'UC59-TP1W',
        259 => 'UC86-TP3W',
        260 => 'UC86-TP4W',
        261 => 'UC112-TP2W',
        262 => 'UC112-TP3W',
        263 => 'UC112-TP1W',
        264 => 'UC93-TP1W',
        265 => 'UC93-TP2W',
        266 => 'ETCS-OTP002-SoM',
        267 => 'UC76-TP4W',
        268 => 'UC76-TP2W',
        269 => 'UC76-TP3W',
        282 => 'UC5-TP2',
        283 => 'UC5-TP5',
        284 => 'UC5-TP3',
        285 => 'UC5-TP7',
        286 => 'UC5-TP6',
        287 => 'UC5-TP8',
        288 => 'UC5-TP9',
        289 => 'UC5-TP10',
        290 => 'UC5-TP11',
        291 => 'UC5-TP24',
        292 => 'UC5-TP25',
        293 => 'UC5-TP28',
        294 => 'UC5-TP32',
        295 => 'UC5-TP30',
        296 => 'UC5-TP33',
        297 => 'UC13-TP7',
        298 => 'UC13-TP10',
        299 => 'UC13-TP12',
        300 => 'UC13-TP14',
        302 => 'UC13-TP17',
        306 => 'UC5-TP4',
        307 => 'UC5-TP23',
        308 => 'UC5-TP31',
        309 => 'UC39-TP1W',
        310 => 'UC39-TP2',
        311 => 'UC42-TP1',
        312 => 'UC42-TP2',
        313 => 'UC42-TP3',
        314 => 'SYS_TP_0024',
        315 => 'SYS_TP_0046',
        316 => 'SYS_TP_0049',
        318 => 'SYS_TP_0050',
        319 => 'UC76-TP2',
        320 => 'UC76-TP3',
        321 => 'UC76-TP4',
        322 => 'SYS_TP_0016',
        323 => 'SYS_TP_0007',
        324 => 'SYS_TP_0011',
        325 => 'SYS_TP_0017',
        326 => 'SYS_TP_0018',
        327 => 'SYS_TP_0019',
        328 => 'SYS_TP_0020',
        329 => 'SYS_TP_0021',
        330 => 'SYS_TP_0022',
        331 => 'SYS_TP_0023',
        332 => 'SYS_TP_0047',
        333 => 'SYS_TP_0045',
        336 => 'SYS_TP_0042',
        337 => 'SYS_TP_0052',
        338 => 'SYS_TP_0053',
        339 => 'SYS_TP_0055',
        340 => 'SYS_TP_0056',
        341 => 'SYS_TP_0057',
        342 => 'SYS_TP_0060',
        343 => 'SYS_TP_0059',
        344 => 'SYS_TP_0061',
        345 => 'UC42-TP1W',
        346 => 'UC52-TP4W',
        347 => 'UC42-TP2W',
        348 => 'UC52-TP1W',
        349 => 'UC52-TP2W',
        350 => 'UC52-TP3W',
        351 => 'UC85-TP1W',
        352 => 'UC42-TP3W',
        353 => 'UC85-TP2W',
        354 => 'UC52-TP5W',
        355 => 'UC52-TP6W',
        356 => 'UC52-TP7W',
        357 => 'UC52-TP8W',
        358 => 'UC56-TP1W',
        359 => 'UC56-TP2W',
        360 => 'UC56-TP3W',
        361 => 'UC56-TP4W',
        362 => 'UC56-TP5W',
        363 => 'UC90-TP1W',
        364 => 'UC90-TP2W',
        365 => 'UC91-TP1W',
        366 => 'UC91-TP2W',
        367 => 'UC91-TP3W',
        368 => 'UC91-TP4W',
        369 => 'SYS_TP_0062',
        378 => 'SYS_TP_0008',
        379 => 'UC81-TP1W',
        380 => 'UC81-TP2W',
        381 => 'UC107-TP1W',
        382 => 'UC107-TP2W',
        390 => 'UC76-TP10W',
        391 => 'UC76-TP11W',
        392 => 'UC29-TP1W',
        393 => 'UC29-TP2W',
        394 => 'UC29-TP3W',
        395 => 'UC29-TP4W',
        396 => 'UC77-TP1W',
        397 => 'UC82-TP1W',
        398 => 'UC82-TP2W',
        399 => 'UC39-TP2W',
        409 => 'SYS_TP_0065',
        424 => 'UC30-TP1W',
        455 => 'UC80-TP1W',
        456 => 'UC80-TP2W',
        457 => 'UC83-TP1W',
        458 => 'UC83-TP2W',
        459 => 'UC83-TP3W',
        522 => 'UC98-TP1W',
        523 => 'UC98-TP2W',
        525 => 'UC98-TP3W',
        526 => 'UC58-TP2W',
        527 => 'UC17-TP1',
        528 => 'UC105-TP1W',
        529 => 'UC105-TP2W',
        536 => 'UC37-TP3W',
        537 => 'UC37-TP4W',
        538 => 'ETCS-OTP913-TRN',
        539 => 'UC37-TP5W',
        540 => 'SYS_TP_0073',
        541 => 'SYS_TP_0072',
        542 => 'SYS_TP_0009',
        545 => 'SYS_TP_0004',
        546 => 'SYS_TP_0005',
        547 => 'SYS_TP_0006',
        553 => 'SYS_TP_0025',
        559 => 'UC30-TP2W',
        562 => 'UC115-TP1W',
        563 => 'UC27-TP2',
        564 => 'UC27-TP3',
        565 => 'UC27-TP4',
        566 => 'UC27-TP5',
        567 => 'UC27-TP6',
        568 => 'UC28-TP1',
        569 => 'UC28-TP2',
        570 => 'UC28-TP6',
        571 => 'UC71-TP1',
        573 => 'UC39-TP3W',
        574 => 'UC71-TP2',
        577 => 'UC59-TP1',
        581 => 'UC76-TP10',
        582 => 'UC76-TP11',
        585 => 'UC39-TP1',
        586 => 'UC39-TP3',
        587 => 'UC37-TP3',
        588 => 'UC37-TP4',
        589 => 'UC37-TP5',
        602 => 'SYS_TP_0026',
        603 => 'SYS_TP_0010',
        604 => 'SYS_TP_0027',
        605 => 'SYS_TP_0028',
        606 => 'SYS_TP_0036',
        607 => 'SYS_TP_0048',
        608 => 'SYS_TP_0051',
        609 => 'SYS_TP_0067',
        610 => 'SYS_TP_0068',
        611 => 'SYS_TP_1001',
        612 => 'SYS_TP_1003',
        613 => 'SYS_TP_1002',
        614 => 'SYS_TP_0071',
        615 => 'SYS_TP_0070',
        616 => 'SYS_TP_0069',
        617 => 'SYS_TP_1004',
        626 => 'UC80-TP1',
        627 => 'UC80-TP2',
        628 => 'UC81-TP1',
        629 => 'UC81-TP2',
        630 => 'UC83-TP2',
        631 => 'UC83-TP3',
        632 => 'UC90-TP1',
        633 => 'UC90-TP2',
        636 => 'UC91-TP1',
        637 => 'UC91-TP2',
        638 => 'UC91-TP3',
        639 => 'UC91-TP4',
        641 => 'UC93-TP1',
        642 => 'UC93-TP2',
        643 => 'UC98-TP1',
        644 => 'UC98-TP2',
        645 => 'UC98-TP3',
        648 => 'UC101-TP4',
        649 => 'UC105-TP1',
        650 => 'UC105-TP2',
        651 => 'UC115-TP1',
        654 => 'UC115-TP2',
        685 => 'UC44-TP5',
        686 => 'UC38-TP2',
        687 => 'UC38-TP1',
        688 => 'ETCS-OTP612-LX',
        689 => 'UC77-TP2',
        690 => 'UC77-TP3',
        705 => 'UC95-TP1',
        706 => 'UC95-TP2',
        707 => 'UC95-TP3',
        708 => 'UC95-TP4',
        756 => 'UC32-TP5',
        916 => 'UCX-Taskmanagement-TP1',
        917 => 'UCX-Taskmanagement-TP2',
        918 => 'UCX-Taskmanagement-TP3',
        919 => 'UCX-Taskmanagement-TP4',
        920 => 'FIT-TOCIF-W-ADP01',
        921 => 'FIT-TOCIF-W-ADP02',
        922 => 'FIT-TOCIF-W-CSP01',
        923 => 'FIT-TOCIF-W-CSP02',
        924 => 'FIT-TOCIF-W-D01',
        925 => 'FIT-TOCIF-W-D02',
        926 => 'FIT-TOCIF-W-D03',
        927 => 'FIT-TOCIF-W-D04',
        928 => 'FIT-TOCIF-W-D05',
        929 => 'FIT-TOCIF-W-DLS01',
        930 => 'FIT-TOCIF-W-DLS02',
        931 => 'FIT-TOCIF-W-FC01',
        932 => 'FIT-TOCIF-W-FC02',
        933 => 'FIT-TOCIF-W-FC03',
        934 => 'FIT-TOCIF-W-FC05',
        935 => 'FIT-TOCIF-W-FC04x',
        936 => 'FIT-TOCIF-W-FC06',
        937 => 'FIT-TOCIF-W-HON01',
        938 => 'FIT-TOCIF-W-HON02',
        939 => 'FIT-TOCIF-W-HON03',
        940 => 'FIT-TOCIF-W-MT01',
        941 => 'FIT-TOCIF-W-PC01',
        942 => 'FIT-TOCIF-W-PC02',
        943 => 'FIT-TOCIF-W-PC03',
        944 => 'FIT-TOCIF-W-PC04',
        945 => 'FIT-TOCIF-W-PC05',
        946 => 'FIT-TOCIF-W-PC06',
        947 => 'FIT-TOCIF-W-PC07',
        948 => 'FIT-TOCIF-W-PC08',
        949 => 'FIT-TOCIF-W-TC01',
        950 => 'FIT-TOCIF-W-TC02',
        951 => 'FIT-TOCIF-W-TC03',
        952 => 'FIT-TOCIF-W-TC04',
        953 => 'FIT-TOCIF-W-TC05',
        954 => 'FIT-TOCIF-W-TC06',
        955 => 'FIT-TOCIF-W-TC07',
        956 => 'FIT-TOCIF-W-TC08',
        957 => 'FIT-TOCIF-W-TC09',
        958 => 'FIT-TOCIF-W-TC10',
        1093 => 'ITSec-TP-1',
        1094 => 'ITSEC-TP-2',
        1095 => 'ITSEC-TP-3',
        1096 => 'ITSEC-TP-4',
        1126 => 'UC52-TP10',
        1127 => 'PB-TP1',
        1128 => 'PB-TP2',
        1129 => 'PB-TP3-1',
        1130 => 'PB-TP3-2',
        1131 => 'PB-TP4',
        1132 => 'PB-TP5',
        1133 => 'PB-TP6',
        1134 => 'PB-TP7',
        1135 => 'PB-TP8',
        1136 => 'PB-TP9',
        1137 => 'PB-TP10',
        1138 => 'PB-TP11',
        1139 => 'PB-TP12',
        1140 => 'PB-TP13',
        1141 => 'UC28-TP8',
      );

    /**
     * @var NaturalSorting
     */
    protected $testObject;

    /**
     * @before
     */
    public function setUpObject()
    {
        $this->testObject = new NaturalSorting();
        $this->testObject->setIntegerPartLength(4);
        $this->testObject->setFloatPartLength(3);
    }

    /**
     * Testing action on one method.
     */
    public function testGetValueForNaturalSortingBasic()
    {
        $this->assertEquals(
                'UC0050000-TP0010000-WX0003020-W0003009',
                $this->testObject->getValueForNaturalSorting('UC50-TP10-WX3.20-W3.9'),
                'Column not formatted properly!'
            );
    }

    /**
     * Testing action on one method with size limit.
     */
    public function testGetValueForNaturalSortingLengthLimit()
    {
        $longTitle = 'FIT-TrISkkjlkjlkjlkl';
        $shortTitle = 'FIT-';

        $this->testObject->setNaturalSortingValueMaximalLength(4);
        $this->assertEquals(
                $shortTitle,
                $this->testObject->getValueForNaturalSorting($longTitle),
                'Size limit not working correctly'
        );
    }

    /**
     * Method gets all identifier.
     */
    public function testGetValueForNaturalSortingPhpSort()
    {
        $sortPhpNatural = $this->testProceduresUnsorted;
        $sortPhpRegular = $this->testProceduresUnsorted;

        foreach ($sortPhpRegular as $identifierKey => $identifierValue) {
            $sortPhpRegular[$identifierKey] = $this->testObject->getValueForNaturalSorting($identifierValue);
        }

        sort($sortPhpNatural, SORT_NATURAL);
        sort($sortPhpRegular, SORT_STRING);

        foreach ($sortPhpNatural as $identifierKey => $identifierValue) {
            $valueForNaturalSorting = $this->testObject->getValueForNaturalSorting($identifierValue);
            $this->assertEquals($sortPhpRegular[$identifierKey], $valueForNaturalSorting, 'Sorted element not proper!');
        }
    }
}
