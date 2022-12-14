<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

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
     * Get the full path to a file.
     *
     * @return string
     */
    public function getFullPath(): string
    {
        return sprintf("%s/%s.%s", $this->path, $this->name, $this->extension);
    }

    /**
     * Get a new name with extension.
     *
     * @param $name
     * @return string
     */
    public function getNewName($name): string
    {
        return sprintf("%s.%s", $name, $this->extension);
    }

    /**
     * Delete with file.
     *
     * @return void
     */
    public function remove(): void
    {
        Storage::delete($this->getFullPath());
        $this->delete();
    }
}
