<?php
/**
 * Created by PhpStorm.
 * User: mmrdev
 * Date: 23/09/19
 * Time: 10:21 AM
 */

use Illuminate\Support\Str;

if (!function_exists("models")) {
    function modelesName()
    {
        $files = collect(array_merge(glob(base_path("/Modules/*/Entities/*.php")), glob(base_path("/app/*.php"))));
        return $files->map(function ($file) {
            $file = array_reverse(explode("/", $file))[0];
            return str_replace('.php', '', $file);
        });
    }
}

if (!function_exists("is_model")) {
    function is_model(string $modelName): bool
    {
        $modelName = uc($modelName);
        // Modules/*/Entities/*.php
        $files = collect(array_merge(glob(base_path("/Modules/*/Entities/*.php")), glob(base_path("/app/*.php"))));
        $models = $files->map(function ($file) {
            $file = array_reverse(explode("/", $file))[0];
            return str_replace('.php', '', $file);
        });
        return in_array($modelName, $models->toArray());
    }
}

if (!function_exists("getNameSpaceWithModelName")) {
    function getNameSpaceWithModelName(string $modelName): string
    {
        $modelName = uc($modelName);
        // Modules/*/Entities/*.php
        $files = collect(glob(base_path("/Modules/*/Entities/*.php")));
        $models = $files->map(function ($file) use ($modelName) {
            $file = array_reverse(explode("/", $file));
            $model = str_replace('.php', '', $file[0]);
            if ($model == $modelName) {
                return $file[2];
            }
        });
        $module = array_filter($models->toArray());
        if (count($module) == 0) {
            return false;
        }
        return collect($module)->first();
    }
}

if (!function_exists("uc")) {
    function uc(string $string): string
    {
        return Str::ucfirst(Str::lower($string));
    }
}


if (!function_exists("uidGenerate")) {
    function uidGenerate(int $id = null, int $last = null, int $min = 100, int $max = 1000): int
    {
        $num =mt_rand($min, $max);
        return $id . $num . $last;
    }
}
