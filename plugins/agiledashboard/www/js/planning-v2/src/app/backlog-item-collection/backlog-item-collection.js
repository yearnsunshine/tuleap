import angular from 'angular';

import backlog_item_rest from '../backlog-item-rest/backlog-item-rest.js';

import BacklogItemCollectionService from './backlog-item-collection-service.js';

export default angular.module('backlog-item-collection', [
    backlog_item_rest
])
.service('BacklogItemCollectionService', BacklogItemCollectionService)
.name;
