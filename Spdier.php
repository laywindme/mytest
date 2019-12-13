<?php
class Spider {
    private static $queue = [];

    public static function isEmpty () {
        return empty(self::$queue);
    }

    public static function fetch () {
        if (empty(self::$queue)) {
            return null;
        }
        return array_shift(self::$queue);
    }

    public static function add ($url, $type) {
        self::$queue[] = compact('url', 'type');
    }

    public function run () {
        $task = self::fetch();
        $data = self::request($task['url']);
        switch ($task['type']) {
            case 0:
                foreach ($data as $d) {
                    self::add('http://api.shouba.cn:31100/food_library/category_food_list?currPage=1&pageSize=20&foodCategoryId=' . $d['id'], 1);
                    file_put_contents('data/foods/food_categorys.json', json_encode($d) . "\n", FILE_APPEND);
                }
                break;
            case 1:
                if ($data['currPage'] < $data['totalPage']) {
                    self::add('http://api.shouba.cn:31100/food_library/category_food_list?currPage='.($data['currPage'] + 1).'&pageSize=20&foodCategoryId='.$data['list'][0]['foodCategoryId'], 1);
                }
                foreach ($data['list'] as $food) {
                    self::add('https://web.shouba.cn/223api/food_library/food_info?_=1575018254556&userId=1116260&foodId=' . $food['foodId'], 2);
                }
                break;
            case 2:
                file_put_contents('data/foods/cate_'.$data['foodCategoryId'].'_food.json', json_encode($data) . "\n", FILE_APPEND);
                break;
            default:
                break;
        }
    }

    public static function request ($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOiIxMDYyODcwIiwiZXhwIjoxNTc3NDUzODAxfQ.GEpTfP6_EwUU0G2KLVZc4w92bk7TD1Zbcq4UTGAwy7o'
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        $res = json_decode($res, true);
        if ($res['code'] == '200') {
            echo "$url\n";
            return $res['data'];
        }
        return [];
    }
}
if (!file_exists('data/foods')) {
    mkdir('data/foods', 0775, true);
}

Spider::add('https://web.shouba.cn/220api/food_library/category_list', 0);
$spider = new Spider();
while (!Spider::isEmpty()) {
    $urls = $spider->run();
}