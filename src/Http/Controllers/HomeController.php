<?php

namespace Jhumanj\LaravelModelStats\Http\Controllers;

use Schema;
use ReflectionClass;
use Illuminate\Container\Container;
use Illuminate\Contracts\View\View;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Contracts\Foundation\Application;

class HomeController extends Controller
{
    public const FRONT_END_VERSION = 6;

    public function home(): Factory|View|Application
    {
        return view('model-stats::dashboard', [
            'config' => $this->modelStatsConfig(),
            'models' => $this->getModels(),
        ]);
    }

    private function modelStatsConfig(): array
    {
        return [
            'appName' => config('app.name'),
            'path' => config('model-stats.routes_prefix'),
            'frontEndVersion' => self::FRONT_END_VERSION,
        ];
    }

    private function getModels(): Collection
    {
        $models = collect(File::allFiles(app_path()))
            ->map(function ($item) {
                $path = $item->getRelativePathName();
                return sprintf(
                    '\%s%s',
                    Container::getInstance()->getNamespace(),
                    strtr(substr($path, 0, strrpos($path, '.')), '/', '\\')
                );
            })
            ->filter(function ($class) {
                $valid = false;

                if (class_exists($class)) {
                    $reflection = new ReflectionClass($class);
                    $valid = $reflection->isSubclassOf(Model::class) &&
                        ! $reflection->isAbstract();
                }

                return $valid;
            });


        return $models->map(fn(string $class) => [
            'class' => $class,
            'fields' => $this->getClassFields($class),
        ])->sortByDesc('class')->values();
    }

    private function getClassFields(string $class)
    {
        return Schema::getColumnListing((new $class)->getTable());
    }
}
