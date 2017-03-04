<?php
// @codingStandardsIgnoreFile
// @codeCoverageIgnoreStart
// this is an autogenerated file - do not edit
function autoload1331aa9e3bed589b86ed43e3a15fcb3c($class) {
    static $classes = null;
    if ($classes === null) {
        $classes = array(
            'frsplugin' => '/frsPlugin.class.php',
            'tuleap\\frs\\additionalinformationpresenter' => '/AdditionalInformationPresenter.php',
            'tuleap\\frs\\agiledashboardpaneinfo' => '/FRS/AgileDashboardPaneInfo.php',
            'tuleap\\frs\\artifactview' => '/FRS/ArtifactView.php',
            'tuleap\\frs\\link\\dao' => '/Link/Dao.php',
            'tuleap\\frs\\link\\retriever' => '/Link/Retriever.php',
            'tuleap\\frs\\link\\updater' => '/Link/Updater.php',
            'tuleap\\frs\\plugindescriptor' => '/FRS/PluginDescriptor.php',
            'tuleap\\frs\\plugininfo' => '/FRS/PluginInfo.php',
            'tuleap\\frs\\releasepresenter' => '/FRS/ReleasePresenter.php',
            'tuleap\\frs\\rest\\resourcesinjector' => '/FRS/REST/ResourcesInjector.php',
            'tuleap\\frs\\rest\\v1\\filerepresentation' => '/FRS/REST/v1/FileRepresentation.php',
            'tuleap\\frs\\rest\\v1\\packagerepresentation' => '/FRS/REST/v1/PackageRepresentation.php',
            'tuleap\\frs\\rest\\v1\\packagerepresentationpaginatedcollection' => '/FRS/REST/v1/PackageRepresentationPaginatedCollection.php',
            'tuleap\\frs\\rest\\v1\\packageresource' => '/FRS/REST/v1/PackageResource.php',
            'tuleap\\frs\\rest\\v1\\projectresource' => '/FRS/REST/v1/ProjectResource.php',
            'tuleap\\frs\\rest\\v1\\releaserepresentation' => '/FRS/REST/v1/ReleaseRepresentation.php',
            'tuleap\\frs\\rest\\v1\\releaseresource' => '/FRS/REST/v1/ReleaseResource.php'
        );
    }
    $cn = strtolower($class);
    if (isset($classes[$cn])) {
        require dirname(__FILE__) . $classes[$cn];
    }
}
spl_autoload_register('autoload1331aa9e3bed589b86ed43e3a15fcb3c');
// @codeCoverageIgnoreEnd
