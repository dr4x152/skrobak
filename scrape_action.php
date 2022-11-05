<?php
require_once 'db.php';
class Database
{
    public $pdo;
    function __construct($pdo)
    {
        $this->pdo = $pdo;
    }
    function getCat()
    {
        $query = $this->pdo->prepare('SELECT * FROM zbudowy_catagory_main');
        $query->execute();
        while ($row = $query->fetch()) {
            echo '<option value="' . $row['cat_id'] . '">' . $row['cat_name'] . '</option>';
        }
    }
    function getScat($postCategory)
    {
        $query = $this->pdo->prepare('SELECT * FROM zbudowy_catagory_sub WHERE main_cat_id=' . $postCategory);
        $query->execute();
        echo '<option></option>';
        while ($row = $query->fetch()) {
            echo '<option value="' . $row['sub_cat_id'] . '">' . $row['sub_cat_name'] . '</option>';
        }
    }
}
class Check extends Database
{
    public $link;
    public $cat;
    public $scat;
    public $arrayLinks = array();
    public $pdo;
    public $countLinks = 0;
    public $checking;
    public function __construct($postLink, $postCat, $postScat, $pdo, $checking)
    {
        $this->link = $postLink;
        $this->cat = $postCat;
        $this->scat = $postScat;
        $this->pdo = $pdo;
        $this->checking = $checking;
    }

    //Pobieranie danych ze strony
    function Scrape($newLink)
    {
        if ($this->link !== '') {

            //Zapisywanie strony
            $site = file_get_contents($newLink);
            libxml_use_internal_errors(TRUE);
            $dom1 = new DOMDocument();
            $dom1->loadHTML($site);
            $xml1 = simplexml_import_dom($dom1);
            libxml_use_internal_errors(FALSE);

            //Wyszukiwanie obiektów z linkami
            foreach ($xml1->xpath("//ul[contains(@class, 'list') and contains(@class, 'normal')]/li/article/ul/li[contains(@class, 'has') and contains(@class, 'photo')]/a/@href") as $item1) {
                $allLinks = (string)$item1 . PHP_EOL;
                array_push($this->arrayLinks, $allLinks);
                $nextLink = file_get_contents('https://sprzedajemy.pl' . $allLinks);

                //Zapisywanie podstrony z każdego linku
                libxml_use_internal_errors(TRUE);
                $dom2 = new DOMDocument();
                $dom2->loadHTML($nextLink);
                $xml2 = simplexml_import_dom($dom2);
                libxml_use_internal_errors(FALSE);

                //Wyszukiwanie obiektów na podstronie
                //Tytuł
                foreach ($xml2->xpath("//span[@class='isUrgentTitle']") as $item2) {
                    $title = (string)$item2 . PHP_EOL;
                }

                // SLUG
                $slugPre = mb_strtolower($title);
                $characterArray = array(
                    'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ó' => 'o', 'ń' => 'n', 'ś' => 's', 'ż' => 'z', 'ź' => 'z'
                );
                $slug = preg_replace('/\s+/', '-', preg_replace('/[^a-zA-Z0-9_ -]/s', '', strtr(
                    $slugPre,
                    $characterArray
                )));
                // OPIS

                foreach ($xml2->xpath("//div[@class='offerDescription']/span/text()") as $item2) {
                    $subtitle = (string)$item2 . PHP_EOL;
                }

                // CENA
                foreach ($xml2->xpath("//span[@itemprop='price']") as $item2) {
                    $pricePre = (string)$item2 . PHP_EOL;
                    $price = preg_replace('/[AaĄąBbCcĆćDdEeĘęFfGgHhIiJjKkLlŁłMmNnŃńOoÓóPpRrSsŚśTtUuWwYyZzŹźŻż\s+]/', '', preg_replace('/,/', '.', $pricePre));
                }

                // MIASTO I REGION
                foreach ($xml2->xpath("//span/a[@class='locationName']/text()") as $item2) {
                    $cityPre = (string)$item2 . PHP_EOL;
                    $cityNew = preg_replace('/\s+/', '', (string)$cityPre . PHP_EOL);
                    $query = $this->pdo->prepare("SELECT * FROM zbudowy_cities WHERE name=:uname");
                    $query->execute(array(
                        ':uname' => $cityNew
                    ));
                    $row = $query->fetch();
                    $city = $row['id'];
                    $region = $row['subadmin1_code'];
                }

                // TELEFON
                foreach ($xml2->xpath("//span[@class='phone-number-truncated']/span") as $item2) {
                    $phone1 = preg_replace('/\s+/', '', (string)$item2 . PHP_EOL);
                }
                foreach ($xml2->xpath("//span[@class='phone-number-truncated']/@data-phone-end") as $item2) {
                    $phone2 = preg_replace('/\s+/', '', (string)$item2 . PHP_EOL);
                }
                $phone = $phone1 . $phone2;

                // ERROR
                if ($phone == !null & $city == !null) {
                    $check_query = "SELECT COUNT(*) AS total FROM zbudowy_product WHERE slug='$slug'";
                    $check = $this->pdo->query($check_query);
                    $check_total = $check->fetch();
                    if ($check_total['total'] <= 0) {
                        if ($this->checking === false) {
                            
                            // IMAGE
                            $array_images_d = array();
                            foreach ($xml2->xpath("//img[@class='js-gallerySlide']/@src") as $item2) {
                                $image_link = (string)$item2 . PHP_EOL;
                                array_push($array_images_d, $image_link);
                            }
                            $array_images = array_unique($array_images_d);
                            $array_new_images = array();
                            foreach ($array_images as $value) {
                                $image = uniqid('img_', true) . '.png';
                                $img1 = '../../zbudowy/storage/products/' . $image;
                                file_put_contents($img1, file_get_contents(preg_replace('/\s+/', '', $value)));
                                array_push($array_new_images, $image);
                            }
                            $images = implode(',', preg_replace('/\s+/', '', $array_new_images));
                            $img2 = '../../zbudowy/storage/products/thumb/' . $array_new_images[0];
                            file_put_contents($img2, file_get_contents(preg_replace('/\s+/', '', $array_images[0])));
                            $insert = $this->pdo->prepare("INSERT INTO table_name (status,user_id,featured,urgent,highlight,product_name,slug,description,category,sub_category,price,negotiable,phone,hide_phone,location,city,state,country,latlong,screen_shot,tag,created_at,updated_at,expire_date,emailed)
                                        VALUES (:ustatus,:uuser_id,:ufeatured,:uurgent,:uhighlight,:uproduct_name,:uslug,:udescription,:ucategory,:usub_category,:uprice,:unegotiable,:uphone,:uhide_phone,:ulocation,:ucity,:ustate,:ucountry,:ulatlong,:uscreen_shot,:utag,:ucreated_at,:uupdated_at,:uexpire_date,:uemailed)");
                            if ($insert->execute(array(
                                'ustatus' => 'active',
                                'uuser_id' => 1,
                                'ufeatured' => 0,
                                'uurgent' => 0,
                                'uhighlight' => 0,
                                'uproduct_name' => $title,
                                'uslug' => $slug,
                                'udescription' => $subtitle,
                                'ucategory' => $this->cat,
                                'usub_category' => $this->scat,
                                'uprice' => $price,
                                'unegotiable' => 1,
                                'uphone' => $phone,
                                'uhide_phone' => 0,
                                'ulocation' => '',
                                'ucity' => $city,
                                'ustate' => $region,
                                'ucountry' => 'PL',
                                'ulatlong' => '28.6139391,77.20902120000005',
                                'uscreen_shot' => $images,
                                'utag' => '',
                                'ucreated_at' => date('Y-m-d H:i:s'),
                                'uupdated_at' => date('Y-m-d H:i:s'),
                                'uexpire_date' => strtotime(date('Y-m-d H:i:s', strtotime('+30 days'))),
                                'uemailed' => 1,
                            ))) {

                                // header('refresh:2;url=scrape.php');
                            }
                        }
                        $this->countLinks++;
                    }
                }
            }
        } else {
            echo 'wprowadź link';
        }
    }

    //Przechodzenie na kolejne podstrony
    function Links()
    {
        for ($i = 0; $i <= 120; $i = $i + 30) {
            if ($i >= 30) {
                $newLink = $this->link . '&offset=' . $i;
                $this->Scrape($newLink);
            } else {
                $newLink = $this->link;
                $this->Scrape($newLink);
            }
        }
        echo 'run' . $this->checking;
        if ($this->checking === TRUE) {
            echo 'true' . $this->checking;
            echo 'Znaleziono: ' . count($this->arrayLinks) . '</br>Możliwych do dodania: ' . $this->countLinks;
        } else if ($this->checking === FALSE) {
            echo 'false' . $this->checking;
            echo 'Znaleziono: ' . count($this->arrayLinks) . '</br>Dodano: ' . $this->countLinks;
        }
    }
}

//Sprawdzanie linków
if (isset($_REQUEST['check-links'])) {
    $checking = TRUE;
    $data = new Check($_POST['link'], $_POST['cat'], $_POST['scat'], $pdo, $checking);
    $data->Links();
}

//Dodawanie linków
if (isset($_REQUEST['mass-add'])) {
    $checking = FALSE;
    $data = new Check($_POST['link'], $_POST['cat'], $_POST['scat'], $pdo, $checking);
    $data->Links();
}
if ($_POST['action'] == 'select_cat') {
    $db = new Database($pdo);
    $rows = $db->getCat();
}
if ($_POST['action'] == 'select_scat') {
    $db = new Database($pdo);
    $postCategory = $_POST['category'];
    $rows = $db->getScat($postCategory);
}
