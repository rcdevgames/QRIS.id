<?php 

namespace Rmodz;

class QRIS 
{
    private $user, $pass, $cookie, $today, $from_date, $to_date, $limit, $filter;

    public function __construct($user, $pass, $from_date = null, $to_date = null, $limit = 20, $filter = null){
        $today = date('Y-m-d',strtotime('today'));

        // check data
        if(
            !(is_null($from_date)   || (bool) preg_match('/\d{4,}-\d{2,}-\d{2,}/',$from_date)) || 
            !(is_null($to_date)     || (bool) preg_match('/\d{4,}-\d{2,}-\d{2,}/',$to_date))
        ) throw new \Exception("Harap input tanggal dengan format yang benar.\neg: {$today}", 1);
        if(is_int($limit) && !($limit >= 10 && $limit <=300)) throw new \Exception("Harap input limit dengan benar, min 10 & max 300", 1);
        if(!(is_null($filter) || is_int($filter)) || $filter === 0) throw new \Exception("Harap input Nominal dengan bilangan bulat & minimal 0, eg: 100000", 1);
        
        // store data
        $this->today = $today;
        $this->user = $user;
        $this->pass = $pass;
        $this->cookie = "{$this->user}_cookie.txt";

        $this->from_date = is_null($from_date) ? $this->today : $from_date;
        $this->to_date = is_null($to_date) ? $this->today : $to_date;
        $this->limit = $limit;
        $this->filter = is_null($filter) ? $this->filter : $filter;
    }

    public function mutasi(){
        $try = 0;
        relogin:
        $this->url = 'https://m.qris.id/kontenr.php?idir=pages/historytrx.php';
        $this->data = $this->filter_data();

        $res = $this->request();
        
        if(!preg_match("/logout/",$res)){
            $try += 1;
            if($try > 3) throw new \Exception("Gagal login setelah 3x percobaan", 1);

            $this->login();
            goto relogin;
        }

        preg_match_all('/<tr><td class="text-center">(\d+)<\/td><td class="text-center">(.*?)<\/td><td class="text-right ">(\d+)<\/td><td class="text-center">(.*?)<\/td><td>(\d+)<\/td><td class="text-center">(.*?)<\/td><td>(.*?)<\/td><td>(.*?)<\/td><\/tr>/',$res,$data,PREG_SET_ORDER,0);
        
        $result = [];
        foreach($data as $qris){
            $result[] = [
                'id' => $qris[1],
                'date' => strtotime($qris[2]),
                'nominal' => $qris[3],
                'status' => trim(strip_tags($qris[4])),
                'inv_id' => $qris[5],
                'settlement_date' => trim(strip_tags($qris[6])),
                'origin' => $qris[7]
            ];
        }
        
        return $result;
    }

    private function request(){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie );
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie );
        
        if(isset($this->data)){
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->data);
            $headers = array();
            $headers[] = 'Content-Type: multipart/form-data; boundary=---------------------------';
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    private function login(){
        $this->url = 'https://m.qris.id/login.php?pgv=go';
        $this->data = $this->login_data();
        if(!preg_match('/historytrx/',$this->request())){
            unlink($this->cookie);
            throw new \Exception("Tidak dapat login, Harap cek kembali email & password anda", 1);
        }
        
        return true;
    }

    private function login_data(){
        $data = <<<data
            -----------------------------
            Content-Disposition: form-data; name="username"

            {$this->user}
            -----------------------------
            Content-Disposition: form-data; name="password"

            {$this->pass}
            -----------------------------
            Content-Disposition: form-data; name="submitBtn"
        data;

        return preg_replace('/^ +/m','',$data);
    }

    private function filter_data(){
        $data = <<<data
            -----------------------------
            Content-Disposition: form-data; name="datexbegin"

            {$this->from_date}
            -----------------------------
            Content-Disposition: form-data; name="datexend"

            {$this->to_date}
            -----------------------------
            Content-Disposition: form-data; name="limitasidata"

            {$this->limit}
            -----------------------------
            Content-Disposition: form-data; name="searchtxt"

            {$this->filter}
            -----------------------------
            Content-Disposition: form-data; name="Filter"

            Filter
            -------------------------------
        data;

        return preg_replace('/^ +/m','',$data);
    }
}
