<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class File extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'files';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'name', 'mime', 'path', 'user_id', 'updated_at'
    ];

    /**
     * Get folder data.
     *
     * @return HasOne
     */
    public function folder(): HasOne
    {
        return $this->hasOne(
            'App\Models\Folder',
            'id',
            'folder_id'
        );
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return sprintf("%s/%s.%s", $this->path, $this->name, $this->extension);
    }
}
