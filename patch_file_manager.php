<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectFileServicePatch
{
    public static function apply()
    {
        \ = file_get_contents(app_path('Services/ProjectFileService.php'));
        
        // 1. Update uploadFile signature
        \ = str_replace(
            'public function uploadFile(string \, UploadedFile \): string',
            'public function uploadFile(string \, UploadedFile \, bool \ = false): string',
            \
        );
        
        // 2. Update uploadFile overwrite logic
        \ = <<<'EOF'
        \ = \.DIRECTORY_SEPARATOR.\;
        if (file_exists(\)) {
            abort(422, 'File đã tồn tại. Hãy đổi tên hoặc xóa file cũ.');
        }

        \->move(\, \);
EOF;
        \ = <<<'EOF'
        \ = \.DIRECTORY_SEPARATOR.\;
        if (file_exists(\) && !\) {
            abort(422, 'File đã tồn tại. Hãy đổi tên hoặc xóa file cũ.');
        }

        \->move(\, \);
EOF;
        \ = str_replace(\, \, \);

        // 3. Update uploadBatch signature
        \ = str_replace(
            'public function uploadBatch(string \, array \): array',
            'public function uploadBatch(string \, array \, bool \ = false): array',
            \
        );

        // 4. Update uploadBatch overwrite logic
        \ = <<<'EOF'
                \ = \.DIRECTORY_SEPARATOR.\;
                if (file_exists(\)) {
                    \[] = \;
                    continue;
                }

                \->move(\, \);
EOF;
        \ = <<<'EOF'
                \ = \.DIRECTORY_SEPARATOR.\;
                if (file_exists(\) && !\) {
                    \[] = \;
                    continue;
                }

                \->move(\, \);
EOF;
        \ = str_replace(\, \, \);
        
        file_put_contents(app_path('Services/ProjectFileService.php'), \);
        echo "ProjectFileService patched.\n";


        // Controller Patch
        \ = file_get_contents(app_path('Http/Controllers/ProjectFileController.php'));
        
        // Update batchUpload
        \ = <<<'EOF'
            'paths.*' => 'nullable|string|max:500',
        ]);

        \ = (string) (\['path'] ?? '');
EOF;
        \ = <<<'EOF'
            'paths.*' => 'nullable|string|max:500',
            'overwrite' => 'nullable|boolean',
        ]);

        \ = (string) (\['path'] ?? '');
EOF;
        \ = str_replace(\, \, \);
        
        \ = <<<'EOF'
        \ = \->files->uploadBatch(\, \);
EOF;
        \ = <<<'EOF'
        \ = \->files->uploadBatch(\, \, !empty(\['overwrite']));
EOF;
        \ = str_replace(\, \, \);
        
        file_put_contents(app_path('Http/Controllers/ProjectFileController.php'), \);
        echo "ProjectFileController patched.\n";
    }
}
ProjectFileServicePatch::apply();
