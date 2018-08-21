<?php
namespace frontwise\entryrelationsmanager\controllers;

use frontwise\entryrelationsmanager\EntryRelationsManager;

use Craft;
use craft\db\Query;
use craft\elements\Entry;
use craft\elements\db\EntryQuery;
use craft\fields\Entries as EntriesField;
use craft\helpers\StringHelper;
use craft\services\Fields;
use craft\web\Controller;
use frontwise\entryrelationsmanager\web\assets\EntryRelationsManagerAssets;
use craft\helpers\Db;
use yii\base\Exception;

class FieldsController extends Controller
{

    // Public Methods
    // =========================================================================

    public function actionIndex(): craft\web\Response
    {
        $showSiteMenu = Craft::$app->getRequest()->getParam('showSiteMenu', 'auto');
        if ($showSiteMenu !== 'auto') {
            $showSiteMenu = (bool)$showSiteMenu;
        }

        // Register assets
        $this->view->registerAssetBundle(EntryRelationsManagerAssets::class);

        return $this->renderTemplate('entry-relations-manager/fields/index', [
            'showSiteMenu' => $showSiteMenu,
        ]);
    }

    public function actionFields(): craft\web\Response
    {
        $this->requirePostRequest();
        $this->requireLogin();
        $request = Craft::$app->getRequest();
        $siteId = $request->getBodyParam('siteId');
        if (!$siteId) {
            if (!Craft::$app->getIsMultiSite()) {
                $siteId = NULL;
            } else {
                $siteId = Craft::$app->getSites()->currentSite->id;
            }
        }

        $fields = [];

        // Get all fields
        foreach (Craft::$app->getFields()->getAllFields() as $field) {
            // Filter Entries fields
            if ($field instanceof EntriesField) {
                // Get targeted entries, this is already grouped on entry
                $targets = Entry::find()
                    ->innerJoin('{{%relations}} r', '[[r.targetId]] = [[elements.id]]')
                    ->andWhere(['r.fieldId' => $field->id])
                    ->siteId($siteId)
                    ->groupBy(['r.targetId'])
                    ->all()
                ;

                // Count relations per target
                $countPerTarget = (new Query)
                    ->select(['targetId', 'COUNT(*) as count'])
                    ->from('{{%relations}}')
                    ->where(['sourceSiteId' => $siteId])
                    ->andWHere(['fieldId' => $field->id])
                    ->groupBy(['targetId'])
                    ->all();
                $targetsCount = [];
                foreach ($countPerTarget as $row) {
                    $targetsCount[$row['targetId']] = $row['count'];
                }

                $fields[] = [
                    'count' => count($targets),
                    'field' => $field,
                    'targets' => $targets,
                    'targetsCount' => $targetsCount,
                    'relationsCount' => array_sum($targetsCount),
                ];
            }
        }

        $html = $this->getView()->renderTemplate('entry-relations-manager/fields/fields', [
            'fields' => $fields,
            'siteId' => $siteId,
        ]);

        return $this->asJson(['html' => $html]);
    }

    // Remove relations
    public function actionRemove(): craft\web\Response
    {
        $this->requirePostRequest();
        $this->requireLogin();
        $request = Craft::$app->getRequest();
        $siteId = $request->getBodyParam('siteId');
        if (!$siteId) {
            if (!Craft::$app->getIsMultiSite()) {
                $siteId = NULL;
            } else {
                $siteId = Craft::$app->getSites()->currentSite->id;
            }
        }
        $fieldId = $request->getBodyParam('fieldId');
        $targetId = $request->getBodyParam('targetId');

        // Get relations to existing elements in current site that should be kept
        $relations = (new Query)
            ->select('r.id')
            ->from('{{%relations}} r')
            ->leftJoin('{{%elements_sites}} e', '[[r.targetId]] = [[e.elementId]]')
            ->andWhere(['r.fieldId' => $fieldId])
            ->andWhere(['e.siteId' => $siteId])
            ->all();

        $relationIds = [];

        // If no target entry is set, keep existing relations. Otherwise just remove relations to target Entry
        if (!$targetId) {
            foreach ($relations as $relation) {
                $relationIds[] = $relation['id'];
            }
        }

        $params =
        [
            'and',
            ['sourceSiteId' => $siteId],
            ['fieldId' => $fieldId],
            ['not in', 'id', $relationIds]
        ];

        if ($targetId) {
            $params[] = ['targetId' => $targetId];
        }

        // Remove other relations
        Db::deleteIfExists('{{%relations}}', $params);

        // Return the remaining fields
        return $this->actionFields();
    }

    public function actionReplace(): craft\web\Response
    {
        $this->requirePostRequest();
        $this->requireLogin();
        $request = Craft::$app->getRequest();
        $siteId = $request->getBodyParam('siteId');
        if (!$siteId) {
            $siteId = Craft::$app->getSites()->currentSite->id;
        }
        $fieldId = $request->getBodyParam('fieldId');
        $targetId = $request->getBodyParam('targetId');
        $newTargetId = $request->getBodyParam('newTargetId');

        if (!$fieldId || !$targetId || !$newTargetId) {
            Craft::error();
            throw new Exception('Couldn’t replace element relations, missing required parameters.');
        }

        // Select existing relations that should be updated
        $relations = (new Query)
            ->select(['r.id', 'sourceId'])
            ->from('{{%relations}} r')
            ->leftJoin('{{%elements_sites}} e', '[[r.targetId]] = [[e.elementId]]')
            ->andWhere(['r.fieldId' => $fieldId])
            ->andWhere(['r.targetId' => $targetId])
            ->andWhere(['e.siteId' => $siteId])
            ->all();
        $relationIds = [];
        foreach ($relations as $relation) {
            if ($relation['sourceId'] == $newTargetId) {
                // Can't relate an entry to itself
                continue;
            }
            $relationIds[] = $relation['id'];
        }

        // Update relations to the new target
        Craft::$app->getDb()->createCommand()
            ->update(
                '{{%relations}}',
                [
                    'targetId' => $newTargetId,
                ],
                [
                    'in',
                    'id',
                    $relationIds,
                ]
            )
            ->execute();

        // Return new fields
        return $this->actionFields();
    }
}
