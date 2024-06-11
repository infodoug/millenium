<?php

class Post
{
    private $error = '';
    public function create_post($userid, $data)
    {
        if(!empty($data['post']))
        {
            $post = addslashes($data['post']);
            $postid = $this->create_postid();

            return [$postid, $post];
        } else
        {
            $this->error = "Digite algo para postar.<br>";
            return $this->error;
        }

        
    }

    private function create_postid()
    {
        $length = rand(4,19);
        $number = "";
        for ($i=0; $i < $length; $i++) {
            $new_rand = rand(0,9);
            $number = $number . $new_rand;
        }
        return $number;
    }

}

?>