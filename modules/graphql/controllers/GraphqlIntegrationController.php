<?php

defined('BASEPATH') or exit('No direct script access allowed');

// Load Composer autoloader
require_once(__DIR__ . '/../vendor/autoload.php');

use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;

class GraphqlIntegrationController extends CI_Controller {
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->validate_token(); // Validate the token on every request
    }

    private function validate_token() {
        // Get the token from the headers
        $headers = $this->input->request_headers();
        $token = isset($headers['authtoken']) ? $headers['authtoken'] : null;

        // Retrieve the stored token from the database
        $stored_token = get_option('graphqltoken');

        // Validate the token
        if ($token !== $stored_token) {
            // Deny access if the token doesn't match
            header('Content-Type: application/json');
            echo json_encode(['error' => ['message' => 'Unauthorized access']]);
            http_response_code(403); // Forbidden
            exit; // Stop further execution
        }
    }

	public function index()
	{

		$this->load->library('app_modules');
		if (!$this->app_modules->is_active('graphql')) {
			header('Content-Type: application/json');
			echo json_encode(['error' => ['message' => 'GraphQL Module is not active']]);
			http_response_code(403); // Forbidden
			return;
		}

		$tables = $this->db->list_tables();

		if (empty($tables)) {
			header('Content-Type: application/json');
			echo json_encode(['error' => ['message' => 'No tables found in the database.']]);
			return;
		}

        // Dynamically create GraphQL types
        $queryFields = [];
        foreach ($tables as $table) {
            $queryFields[$table] = [
                'type' => Type::listOf(new ObjectType([
                    'name' => ucfirst($table),
                    'fields' => function() use ($table) {
                        return $this->getFieldsFromTable($table);
                    },
                ])),
                'args' => [
                    'id_in' => [
                        'type' => Type::listOf(Type::int()), // Allow filtering by an array of integers
                    ],
                ],
                'resolve' => function($root, $args) use ($table) {
                    if (isset($args['id_in']) && !empty($args['id_in'])) {
                        // Apply filtering if 'id_in' argument is passed
                        $this->db->where_in('id', $args['id_in']);
                    }
                    return $this->db->get($table)->result();
                },
            ];
        }

        // Create the Mutation Type
        $mutationFields = [];
        foreach ($tables as $table) {
            // Add Mutation
            $mutationFields['add' . ucfirst($table)] = [
                'type' => new ObjectType([
                    'name' => 'Add' . ucfirst($table),
                    'fields' => array_merge(
                        $this->getFieldsFromTable($table),
                        ['message' => ['type' => Type::string()]]
                    )
                ]),
                'args' => $this->getFieldsFromTable($table),
                'resolve' => function($root, $args) use ($table) {
                    $this->db->insert($table, $args);
                    return ["message" => "{$table} added successfully."];
                },
            ];

            // PUT Mutation
            $mutationFields['update' . ucfirst($table)] = [
                'type' => new ObjectType([
                    'name' => 'Update' . ucfirst($table),
                    'fields' => array_merge(
                        $this->getFieldsFromTable($table),
                        ['message' => ['type' => Type::string()]]
                    )
                ]),
                'args' => array_merge(
                    ['id' => Type::nonNull(Type::int())],
                    $this->getFieldsFromTable($table)
                ),
                'resolve' => function($root, $args) use ($table) {
                    $id = $args['id'];
                    
                    // Remove the id from the data to be updated
                    $dataToUpdate = $args;
                    unset($dataToUpdate['id']); 

                    // Update the database
                    if ($this->db->where('id', $id)->update($table, $dataToUpdate)) {
                        // Get the updated record
                        $updatedRow = $this->db->get_where($table, ['id' => $id])->row_array();
                        // Check if the record exists
                        if ($updatedRow) {
                            return array_merge($updatedRow, ["message" => "{$table} with id {$id} updated successfully."]);
                        } else {
                            return ["message" => "No rows updated. Check if the id exists."];
                        }
                    } else {
                        return ["message" => "No rows updated. Check if the id exists."];
                    }
                },
            ];

            // DELETE Mutation
            $mutationFields['delete' . ucfirst($table)] = [
                'type' => new ObjectType([
                    'name' => 'Delete' . ucfirst($table),
                    'fields' => [
                        'message' => ['type' => Type::string()]
                    ]
                ]),
                'args' => [
                    'id' => Type::nonNull(Type::int()) // ID is required
                ],
                'resolve' => function($root, $args) use ($table) {
                    $id = $args['id'];

                    if ($this->db->delete($table, ['id' => $id])) {
                        return ["message" => "{$table} with id {$id} deleted successfully."];
                    } else {
                        return ["message" => "No rows deleted. Check if the id exists."];
                    }
                },
            ];
        }

        // Create the query type
        $queryType = new ObjectType([
            'name' => 'Query',
            'fields' => $queryFields,
        ]);

        // Create the mutation type
        $mutationType = new ObjectType([
            'name' => 'Mutation',
            'fields' => $mutationFields,
        ]);

        // Create the schema
        $schema = new Schema([
            'query' => $queryType,
            'mutation' => $mutationType,
        ]);

        // Get the input
        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true);
        $query = $input['query'] ?? null;

        // Check if a query is provided
        if (is_null($query)) {
            header('Content-Type: application/json');
            echo json_encode(['error' => ['message' => 'No query provided.']]);
            return;
        }

        try {
            // Execute the query
            $result = GraphQL::executeQuery($schema, $query);
            $output = $result->toArray();
        } catch (\Exception $e) {
            $output = [
                'error' => ['message' => $e->getMessage()]
            ];
        }

        header('Content-Type: application/json');
        echo json_encode($output);
    }

    private function getFieldsFromTable($table)
    {
        // Get the columns of the table
        $fields = [];
        $columns = $this->db->list_fields($table);

        foreach ($columns as $column) {
            // Check if the field is the id to declare the correct type
            $fieldType = ($column == 'id') ? Type::int() : Type::string(); // id as integer, others as string
            
            $fields[$column] = [
                'type' => $fieldType,
                'resolve' => function($row) use ($column) {
                    // Ensure that $row is an object and not an array
                    if (is_array($row)) {
                        $row = (object) $row; // Convert to object
                    }
                    return $row->$column ?? null; // Use null if the field doesn't exist
                }
            ];
        }
        
        return $fields;
    }
}
