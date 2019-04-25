<?php

namespace NTSchool\Phpblog\Model;

class Messages extends BaseModel
{
    /**
     * Messages constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->table = 'news';
        $this->pk = 'id_news';
    }

    /**
     * @return array
     */
    public function validationMap()
    {
        return [
            'fields' => ['title', 'content', 'id_user'],
            'not_empty' => ['title', 'content', 'id_user'],
            'min_length' => [
                'title' => 5,
                'content' => 20
            ]
        ];
    }

    /**
     * @return mixed
     */
    public function getAll()
    {
        return $this->db->select("SELECT * FROM {$this->table} LEFT JOIN users USING(id_user) ORDER BY pub_date DESC");
    }


}