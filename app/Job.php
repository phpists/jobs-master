<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Job extends Model
{
    use SoftDeletes;
    public function users() {
        return $this->belongsToMany('App\User','user_jobs');
    }

    public function images() {
        return $this->hasMany('App\JobImage');
    }

    public function hr() {
        return $this->belongsToMany('App\User','job_hrs');
    }

    public function type_of_year() {
        return $this->belongsToMany('App\TypeOfYear','job_type_of_years');
    }

    public function organizationRoute() {
        return $this->belongsToMany('App\OrganizationRoute','organization_routes_jobs');
    }

    public function city() {
        return $this->belongsTo('App\City');
    }

    public function type() {
        return $this->belongsTo('App\JobType','job_type_id');
    }

    public function category() {
        return $this->belongsTo('App\Category');
    }

    public function subcategory() {
        return $this->belongsTo('App\Subcategory');
    }


    public function organization() {
        return $this->belongsTo('App\Organization');
    }

    public function midrasha() {
        return $this->hasOne('App\JobMidrashaInfo');
    }

    public function reviews() {
        return $this->hasMany('App\JobReview');
    }

    public function jobUsers() {
        return $this->hasMany('App\UserJob');
    }
}
