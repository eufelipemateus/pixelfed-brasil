<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Newsroom extends Model
{
    protected $table = 'newsroom';
    protected $fillable = ['title'];

    protected $casts = [
    	'published_at' => 'datetime'
    ];

    public function permalink()
    {
    	$year = $this->published_at->year;
    	$month = $this->published_at->format('m');
    	$slug = $this->slug;

        return route('newsroom.show', [
            'year' => $year,
            'month' => $month,
            'slug' => $slug
        ]);
    }

    public function editUrl()
    {
        return url("/i/admin/newsroom/edit/{$this->id}");
    }
}
