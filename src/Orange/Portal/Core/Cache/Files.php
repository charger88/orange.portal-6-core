<?php

namespace Orange\Portal\Core\Cache;

class Files implements CacheInterface
{

	private $folder;

	public function __construct($config){
		$this->folder = 'sites/' . \Orange\Portal\Core\App\Portal::$sitecode . '/tmp/cache/system';
	}

	public function get($key){
		$file = new \Orange\FS\File($this->folder, $key . '.txt');
		if ($file->exists()){
			$cache = unserialize($file->getData());
			if (time() <= $cache['expiration']){
				return $cache['data'];
			}
		}
		return null;
	}

	public function set($key, $data, $period = 3600){
		$file = new \Orange\FS\File($this->folder, $key . '.txt');
		$file->save(serialize([
			'expiration' => (time() + $period),
			'data' => $data,
		]));
		return $file->exists();
	}

	public function remove($key, $not_exact = false){
		if (!$not_exact) {
			$file = new \Orange\FS\File($this->folder, $key . '.txt');
			$file->remove();
			return !$file->exists();
		} else {
			$dir = new \Orange\FS\Dir($this->folder);
			if ($dir->exists()){
				$dir = $dir->readDir();
				foreach ($dir as $file){
					if (strpos($file->getName(), $key) === 0){
						$file->remove();
					}
				}
			}
			return true;
		}

	}

	public function reset(){
		$dir = new \Orange\FS\Dir($this->folder);
		if ($dir->exists()){
			$dir = $dir->readDir();
			foreach ($dir as $file){
				$file->remove();
			}
		}
		return true;
	}

	public function clean(){
		$dir = new \Orange\FS\Dir($this->folder);
		if ($dir->exists()){
			$dir = $dir->readDir();
			$timestamp = time();
			foreach ($dir as $file){
				$cache = unserialize($file->getData());
				$expiration = $cache['expiration'];
				if ($timestamp > $expiration){
					$file->remove();
				}
			}
		}
		return true;
	}

}