<?php

class AssetLoader
{
    public static function update()
    {
        if (is_dir('asset')) {
            // Check for removed files
            $fs = new Composer\Util\Filesystem();
            $assets = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
                'asset',
                FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::SKIP_DOTS
            ), RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($assets as $asset) {
                /** @var SplFileInfo $asset */
                if ($asset->isDir()) {
                    if ($fs->isDirEmpty($asset->getPathname())) {
                        rmdir($asset);
                    }
                } elseif (! $asset->isReadable()) {
                    unlink($asset);
                }
            }
        }

        // Check for new files
        $vendorLibs = new FilesystemIterator('vendor/ipl');
        foreach ($vendorLibs as $vendorLib) {
            /** @var SplFileInfo $vendorLib */
            $assetDir = join(DIRECTORY_SEPARATOR, [$vendorLib->getRealPath(), 'asset']);
            if (is_readable($assetDir) && is_dir($assetDir)) {
                $libAssets = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
                    $assetDir,
                    FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::SKIP_DOTS
                ), RecursiveIteratorIterator::SELF_FIRST);
                foreach ($libAssets as $asset) {
                    /** @var SplFileInfo $asset */
                    $relativePath = ltrim(substr($asset->getPathname(), strlen($vendorLib->getRealPath())), '/\\');
                    if (file_exists($relativePath)) {
                        continue;
                    }

                    if ($asset->isDir()) {
                        mkdir($relativePath, 0755, true);
                    } elseif ($asset->isFile()) {
                        symlink($asset->getPathname(), $relativePath);
                    }
                }
            }
        }
    }
}
