<?php

namespace App\Services;

use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasFile
{
    /**
     * @param $file
     * @param string $directory
     * @return mixed
     */
    protected function uploadToDisk($file, $directory, $disk)
    {
        return Storage::disk($disk)->putFile($directory, $file);
        // return Storage::putFile($directory, $file);
    }

    /**
     * @param $model
     * @return void
     */
    protected function deleteFromDisk($model): void
    {
        Storage::disk($model->disk)
            ->delete($model->path);
    }

    /**
     * @return string
     */
    protected function getDisk(): string
    {
        return config('filesystems.default');
    }

    /**
     * @return MorphOne
     */
    public function file(): MorphOne
    {
        return $this->morphOne(File::class, 'fileable');
    }

    /**
     * @return MorphMany
     */
    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }

    /**
     * @return mixed
     */
    public function getFileUrlAttribute()
    {
        return optional($this->file)->url;
    }

    /**
     * @return mixed
     */
    public function getFileNameAttribute()
    {
        return optional($this->file)->name;
    }

    /**
     * @return array
     */
    public function getFileUrlsAttribute(): array
    {
        return $this->files->map(function ($file) {
            return $file->url;
        })->toArray();
    }

    /**
     * @return array
     */
    public function getFileResourcesAttribute(): array
    {
        return $this->files
            ->map(function ($image) {
                return [
                    'url' => $image->url,
                    'file' => [
                        'name' => $image->name,
                        'size' => Storage::exists($image->path) ? (Storage::disk($image->disk)->size($image->path)) : 0,
                        'id' => $image->id
                    ],
                ];
            })
            ->toArray();
    }

    /**
     * @return array
     */
    public function getFileResourcesWithFileableIdAttribute(): array
    {
        return $this->files
            ->map(function ($image) {
                return [
                    'url' => $image->url,
                    'file' => [
                        'name' => $image->name,
                        'size' => Storage::exists($image->path) ? (Storage::disk($image->disk)->size($image->path)) : 0,
                        'id' => $image->id,
                        'fileable_id' => $image->fileable_id
                    ],
                ];
            })
            ->toArray();
    }

    /**
     * @return array
     */
    public function spesificFileResources($wildcard = null, $type = null): array
    {
        $q = $this->files()
            ->when($wildcard, function ($query) use ($wildcard) {
                return $query->where('path', 'like', "%{$wildcard}%");
            })
            ->when($type, function ($query) use ($type) {
                return $query->where('additional_data', 'LIKE', '%' . $type . '%');
            })
            ->get();

        return $q->map(function ($image) {
            return [
                'url' => $image->url,
                'file' => [
                    'name' => $image->name,
                    'size' => Storage::exists($image->path) ? Storage::disk($image->disk)->size($image->path) : 0,
                    'id' => $image->id,
                    'updated_at' => $image->updated_at,
                ],
            ];
        })
            ->toArray();
    }

    /**
     * @return array
     */
    public function getSalesOrderFileResourcesAttribute($wildcard = null, $type = null): array
    {
        return $this->files
        ->map(function ($image) {
            return [
                'url' => $image->url,
                'file' => [
                    'name' => $image->name,
                    'size' => Storage::exists($image->path) ? (Storage::disk($image->disk)->size($image->path)) : 0,
                    'id' => $image->id
                ],
            ];
        })
        ->toArray();
    }

    public function getRootOnlyFileResourcesAttribute(): array
    {
        $q = $this->files()
            ->whereRaw('LENGTH(path) - LENGTH(REPLACE(path, "/", "")) = 1')
            ->get();

        return $q->map(function ($image) {
            return [
                'url' => $image->url,
                'file' => [
                    'name' => $image->name,
                    'size' => Storage::exists($image->path) ? Storage::disk($image->disk)->size($image->path) : 0,
                    'id' => $image->id
                ],
            ];
        })
            ->toArray();
    }

    /**
     * @return null
     */
    public function getThumbnailUrlAttribute()
    {
        if (!empty($this->thumbnail)) {
            foreach (['public'] as $disk) {
                if (Storage::disk($disk)->exists($this->thumbnail)) {
                    return Storage::disk($disk)->url($this->thumbnail);
                }
            }
        }

        return null;
    }

    /**
     * @return void
     */
    public function deleteThumbnail(): void
    {
        if (!empty($this->thumbnail)) {
            foreach (['public'] as $disk) {
                if (Storage::disk($disk)->exists($this->thumbnail)) {
                    Storage::disk($disk)->delete($this->thumbnail);
                    break;
                }
            }
        }
    }

    /**
     * @return null
     */
    public function getCvUrlAttribute()
    {
        if (!empty($this->cv_file)) {
            foreach (['public'] as $disk) {
                if (Storage::disk($disk)->exists($this->cv_file)) {
                    return Storage::disk($disk)->url($this->cv_file);
                }
            }
        }
        return null;
    }

    /**
     * @param $file
     * @param string $directory
     * @param array $params
     * @return mixed
     */
    public function addFile($file, $directory, $params = [])
    {
        $path = $this->uploadToDisk($file, $directory);
        $disk = $this->getDisk();
        $data = compact('path', 'disk') + $params;
        $model = $this->file()
            ->create($data);

        return $model;
    }

    /**
     * @param $file
     * @param string $directory
     * @param array $params
     * @return mixed
     */
    public function saveFile($file, $directory, $params = [])
    {
        $disk = $this->getDisk();
        $path = $this->uploadToDisk($file, $directory, $disk);
        $name = $file->getClientOriginalName();
        $data = compact('name', 'path', 'disk') + $params;

        if ($model = $this->file) {
            $this->deleteFromDisk($model);

            $this->file()
                ->updateOrCreate([
                    'field' => $params['field'],
                    'fileable_id' => $this->id,
                    'fileable_type' => self::class,
                ],
                [
                    'name' => $name,
                    'path' => $path,
                    'disk' => $disk,
                    'type' => 'image',
                    'order' => $params['order'] ?? 0
                ]);
            $model = $model->refresh();
        } else {
            $model = $this->file()
                ->create($data);
        }

        return $model;
    }

    /**
     * @param $files
     * @param string $directory
     * @param array $params
     * @return void
     */
    public function saveFiles($files, $directory, $params = []): void
    {
        $disk = $this->getDisk();
        $files = collect($files)
            ->map(function ($file, $key = null) use ($directory, $disk, $params) {
                $isMulti = is_array($params[0] ?? null);
                $filename = $file->getClientOriginalName();
                $data = [
                    'type' => 'attachment',
                    'name' => $filename,
                    'disk' => $disk,
                    'path' => $this->uploadToDisk($file, $directory, $disk),
                ];

                if ($isMulti) {
                    return $data + $params[$key];
                }

                return $data + $params;
            })
            ->toArray();

        $this->files()
            ->createMany($files);
    }

    /**
     * @param $file
     * @param string $directory
     * @param array $params
     * @return mixed
     */
    public function addBase64File($file, $directory, $params = [])
    {
        $name = (!empty($params['name']) ? $params['name'] : $directory . "_" . strtotime('now') . '.jpg');

        $file = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $file));
        $filePath = "{$directory}/{$name}";
        $success = file_put_contents(public_path() . "/storage/{$filePath}", $file);

        $path = $filePath;
        $disk = $this->getDisk();
        $data = compact('path', 'disk', 'name') + $params;

        $model = $this->file()->create($data);

        return $model;
    }

    /**
     * @param bool $force
     * @return void
     */
    public function deleteFile($force = true): void
    {
        if ($model = $this->file) {
            if ($force) {
                $this->deleteFromDisk($model);
            }

            $this->file()
                ->delete();
        }
    }

    /**
     * @param array $ids
     * @return void
     */
    public function deleteFiles(array $ids = []): void
    {
        $files = empty($ids)
            ? $this->files
            : $this->files->whereIn('id', $ids);

        if ($files->isEmpty()) {
            return;
        }

        $files->each(function ($file) {
            $this->deleteFromDisk($file);
        });

        if (empty($ids)) {
            $this->files()->delete();
        } else {
            $this->files()->whereIn('id', $ids)
                ->delete();
        }
    }

    /**
     * @param Request $request
     * @return void
     */
    protected function removeFiles(Request $request): void
    {
        $ids = explode(',', $request->removed_files);
        $files = File::whereIn('id', $ids)->get();
        $files->each(function ($file) {
            $file->delete();
            Storage::disk($file->disk)
                ->delete($file->path);
        });
    }
}
