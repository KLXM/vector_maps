<?php

$addon = rex_addon::get('vector_maps');

$func = rex_request('func', 'string', '');

// Cache leeren
if ('clear_cache' === $func) {
    if (rex_dir::delete(rex_path::addonCache('vector_maps'))) {
        echo rex_view::success($addon->i18n('cache_cleared'));
    } else {
        echo rex_view::error($addon->i18n('cache_clear_error'));
    }
}

// Einstellungen speichern
if ('save' === $func && rex::getUser()->isAdmin()) {
    $loadFrontend = rex_post('load_frontend', 'int', 1);
    $addon->setConfig('load_frontend', $loadFrontend);
    echo rex_view::success($addon->i18n('settings_saved'));
}

$loadFrontend = (int) $addon->getConfig('load_frontend', 1);

// --- Einstellungsformular ---
$formContent = '
<form action="' . rex_url::currentBackendPage(['func' => 'save']) . '" method="post">

    <fieldset>
        <legend>' . $addon->i18n('settings_frontend_heading') . '</legend>

        <div class="rex-form-group form-group">
            <label class="control-label col-sm-3">' . $addon->i18n('load_frontend_label') . '</label>
            <div class="col-sm-9">
                <select name="load_frontend" class="form-control selectpicker">
                    <option value="1"' . ($loadFrontend === 1 ? ' selected' : '') . '>' . $addon->i18n('load_frontend_yes') . '</option>
                    <option value="0"' . ($loadFrontend === 0 ? ' selected' : '') . '>' . $addon->i18n('load_frontend_no') . '</option>
                </select>
                <p class="help-block">' . $addon->i18n('load_frontend_notice') . '</p>
            </div>
        </div>

    </fieldset>

    <div class="rex-form-panel-footer">
        <button class="btn btn-primary" type="submit">' . $addon->i18n('settings_save') . '</button>
    </div>

</form>
';

$formFragment = new rex_fragment();
$formFragment->setVar('class', 'edit', false);
$formFragment->setVar('title', $addon->i18n('settings'), false);
$formFragment->setVar('body', $formContent, false);
echo $formFragment->parse('core/page/section.php');

// --- Cache-Größe berechnen ---
$cacheDir   = rex_path::addonCache('vector_maps');
$cacheBytes = 0;
$cacheFiles = 0;
if (is_dir($cacheDir)) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($cacheDir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) {
        if ($file->isFile()) {
            $cacheBytes += $file->getSize();
            ++$cacheFiles;
        }
    }
}
$cacheSizeHuman = match(true) {
    $cacheBytes >= 1_073_741_824 => number_format($cacheBytes / 1_073_741_824, 2) . ' GB',
    $cacheBytes >= 1_048_576     => number_format($cacheBytes / 1_048_576, 2)     . ' MB',
    $cacheBytes >= 1_024         => number_format($cacheBytes / 1_024, 2)          . ' KB',
    default                      => $cacheBytes . ' Byte',
};

// --- Cache-Bereich ---
$cacheContent  = '<p>' . $addon->i18n('settings_info') . '</p>';
$cacheContent .= '<table class="table table-condensed" style="max-width:400px;margin-bottom:16px">'
    . '<tr><th style="width:160px">Dateien im Cache</th><td>' . number_format($cacheFiles, 0, ',', '.') . '</td></tr>'
    . '<tr><th>Cache-Größe</th><td><strong>' . $cacheSizeHuman . '</strong></td></tr>'
    . '</table>';
$cacheContent .= '<a class="btn btn-default" href="' . rex_url::currentBackendPage(['func' => 'clear_cache']) . '">'
    . '<i class="rex-icon rex-icon-delete"></i> ' . $addon->i18n('clear_cache')
    . '</a>';

$cacheFragment = new rex_fragment();
$cacheFragment->setVar('title', 'Tile-Cache', false);
$cacheFragment->setVar('body', $cacheContent, false);
echo $cacheFragment->parse('core/page/section.php');
