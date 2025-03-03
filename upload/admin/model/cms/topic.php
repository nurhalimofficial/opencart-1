<?php
namespace Opencart\Admin\Model\Cms;
/**
 * Class Topic
 *
 * @package Opencart\Admin\Model\Cms
 */
class Topic extends \Opencart\System\Engine\Model {
	/**
	 * Add Topic
	 *
	 * @param array<string, mixed> $data
	 *
	 * @return int $topic
	 */
	public function addTopic(array $data): int {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "topic` SET `sort_order` = '" . (int)$data['sort_order'] . "', `status` = '" . (bool)($data['status'] ?? 0) . "'");

		$topic_id = $this->db->getLastId();

		// Description
		foreach ($data['topic_description'] as $language_id => $value) {
			$this->addDescription($topic_id, $language_id, $value);
		}

		// Store
		if (isset($data['topic_store'])) {
			foreach ($data['topic_store'] as $store_id) {
				$this->addStore($topic_id, $store_id);
			}
		}

		// SEO URL
		$this->load->model('design/seo_url');

		foreach ($data['topic_seo_url'] as $store_id => $language) {
			foreach ($language as $language_id => $keyword) {
				$this->model_design_seo_url->addSeoUrl($store_id, $language_id, 'topic_id', $topic_id, $keyword);
			}
		}

		$this->cache->delete('topic');

		return $topic_id;
	}

	/**
	 * Edit Topic
	 *
	 * @param int                  $topic_id
	 * @param array<string, mixed> $data
	 *
	 * @return void
	 */
	public function editTopic(int $topic_id, array $data): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "topic` SET `sort_order` = '" . (int)$data['sort_order'] . "', `status` = '" . (bool)($data['status'] ?? 0) . "' WHERE `topic_id` = '" . (int)$topic_id . "'");

		// Description
		$this->deleteDescription($topic_id);

		foreach ($data['topic_description'] as $language_id => $value) {
			$this->addDescription($topic_id, $language_id, $value);
		}

		// Store
		$this->deleteStore($topic_id);

		if (isset($data['topic_store'])) {
			foreach ($data['topic_store'] as $store_id) {
				$this->addStore($topic_id, $store_id);
			}
		}

		// SEO URL
		$this->load->model('design/seo_url');

		$this->model_design_seo_url->deleteSeoUrlByKeyValue('topic_id', $topic_id);

		foreach ($data['topic_seo_url'] as $store_id => $language) {
			foreach ($language as $language_id => $keyword) {
				$this->model_design_seo_url->addSeoUrl($store_id, $language_id, 'topic_id', $topic_id, $keyword);
			}
		}

		$this->cache->delete('topic');
	}

	/**
	 * Delete Topic
	 *
	 * @param int $topic_id
	 *
	 * @return void
	 */
	public function deleteTopic(int $topic_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "topic` WHERE `topic_id` = '" . (int)$topic_id . "'");

		$this->deleteDescription($topic_id);
		$this->deleteStore($topic_id);

		$this->load->model('design/seo_url');

		$this->model_design_seo_url->deleteSeoUrlByKeyValue('topic_id', $topic_id);

		$this->cache->delete('topic');
	}

	/**
	 * Get Topic
	 *
	 * @param int $topic_id
	 *
	 * @return array<string, mixed>
	 */
	public function getTopic(int $topic_id): array {
		$sql = "SELECT DISTINCT * FROM `" . DB_PREFIX . "topic` `t` LEFT JOIN `" . DB_PREFIX . "topic_description` `td` ON (`t`.`topic_id` = `td`.`topic_id`) WHERE `t`.`topic_id` = '" . (int)$topic_id . "' AND `td`.`language_id` = '" . (int)$this->config->get('config_language_id') . "'";

		$topic_data = $this->cache->get('topic.' . md5($sql));

		if (!$topic_data) {
			$query = $this->db->query($sql);

			$topic_data = $query->row;

			$this->cache->set('topic.' . md5($sql), $topic_data);
		}

		return $topic_data;
	}

	/**
	 * Get Topics
	 *
	 * @param array<string, mixed> $data
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function getTopics(array $data = []): array {
		$sql = "SELECT * FROM `" . DB_PREFIX . "topic` `t` LEFT JOIN `" . DB_PREFIX . "topic_description` `td` ON (`t`.`topic_id` = `td`.`topic_id`) WHERE `td`.`language_id` = '" . (int)$this->config->get('config_language_id') . "'";

		$sort_data = [
			'td.name',
			't.sort_order'
		];

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY `t`.`sort_order`";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
		}

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$key = md5($sql);

		$topic_data = $this->cache->get('topic.' . $key);

		if (!$topic_data) {
			$query = $this->db->query($sql);

			$topic_data = $query->rows;

			$this->cache->set('topic.' . $key, $topic_data);
		}

		return $topic_data;
	}

	/**
	 *	Add Description
	 *
	 *
	 * @param int $topic_id
	 *
	 * @return void
	 */
	public function addDescription(int $topic_id, int $language_id, $data): void {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "topic_description` SET `topic_id` = '" . (int)$topic_id . "', `language_id` = '" . (int)$language_id . "', `image` = '" . $this->db->escape((string)$data['image']) . "', `name` = '" . $this->db->escape($data['name']) . "', `description` = '" . $this->db->escape($data['description']) . "', `meta_title` = '" . $this->db->escape($data['meta_title']) . "', `meta_description` = '" . $this->db->escape($data['meta_description']) . "', `meta_keyword` = '" . $this->db->escape($data['meta_keyword']) . "'");
	}

	/**
	 *	Delete Description
	 *
	 * @param int $topic_id primary key of the attribute record to be fetched
	 *
	 * @return void
	 */
	public function deleteDescription(int $topic_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "topic_description` WHERE `topic_id` = '" . (int)$topic_id . "'");
	}

	/**
	 * Get Descriptions
	 *
	 * @param int $topic_id
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function getDescriptions(int $topic_id): array {
		$topic_description_data = [];

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "topic_description` WHERE `topic_id` = '" . (int)$topic_id . "'");

		foreach ($query->rows as $result) {
			$topic_description_data[$result['language_id']] = [
				'image'            => $result['image'],
				'name'             => $result['name'],
				'description'      => $result['description'],
				'meta_title'       => $result['meta_title'],
				'meta_description' => $result['meta_description'],
				'meta_keyword'     => $result['meta_keyword']
			];
		}

		return $topic_description_data;
	}

	/**
	 * Add Store
	 *
	 * @param int $topic_id
	 * @param int $store_id
	 *
	 * @return void
	 */
	public function addStore(int $topic_id, int $store_id): void {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "topic_to_store` SET `topic_id` = '" . (int)$topic_id . "', `store_id` = '" . (int)$store_id . "'");
	}

	/**
	 * Delete Store
	 *
	 * @param int $topic_id
	 *
	 * @return void
	 */
	public function deleteStore(int $topic_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "topic_to_store` WHERE `topic_id` = '" . (int)$topic_id . "'");
	}

	/**
	 * Get Stores
	 *
	 * @param int $topic_id
	 *
	 * @return array<int, int>
	 */
	public function getStores(int $topic_id): array {
		$topic_store_data = [];

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "topic_to_store` WHERE `topic_id` = '" . (int)$topic_id . "'");

		foreach ($query->rows as $result) {
			$topic_store_data[] = $result['store_id'];
		}

		return $topic_store_data;
	}

	/**
	 * Add Layout
	 *
	 * @param int $topic_id
	 * @param int $store_id
	 * @param int $layout_id
	 *
	 * @return void
	 */
	public function addLayout(int $topic_id, int $store_id, int $layout_id): array {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "topic_to_layout` SET `article_id` = '" . (int)$topic_id . "', store_id = '" . (int)$store_id . "', `layout_id` = '" . (int)$layout_id . "'");
	}

	/**
	 * Delete Layout
	 *
	 * @param int $topic_id
	 *
	 * @return void
	 */
	public function deleteLayout(int $topic_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "topic_to_layout` WHERE `article_id` = '" . (int)$topic_id . "'");
	}

	/**
	 * Delete Layout
	 *
	 * @param int $layout_id
	 *
	 * @return void
	 */
	public function deleteLayoutByLayoutId(int $layout_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "topic_to_layout` WHERE `layout_id` = '" . (int)$layout_id . "'");
	}

	/**
	 * Get Total Topics
	 *
	 * @return int
	 */
	public function getTotalTopics(): int {
		$query = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "topic`");

		return (int)$query->row['total'];
	}
}
