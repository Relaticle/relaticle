<?php

return [
    /*
     * Which column will be used as the order column.
     */
    'order_column_name' => 'order_column',

    /*
     * Define if the models should sort when creating.
     * When true, the package will automatically assign the highest order number to a new model
     */
    'sort_when_creating' => true,

    /*
     * Define if the timestamps should be ignored when sorting.
     * When true, updated_at will not be updated when using setNewOrder
     */
    'ignore_timestamps' => true,
];
