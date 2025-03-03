<?php
namespace Opencart\Admin\Model\Catalog;
/**
 * Class Manufacturer
 *
 * @package Opencart\Admin\Model\Catalog
 */
class Manufacturer extends \Opencart\System\Engine\Model {
	/**
	 * Add Manufacturer
	 *
	 * @param array<string, mixed> $data
	 *
	 * @return int
	 */
	public function addManufacturer(array $data): int {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "manufacturer` SET `name` = '" . $this->db->escape((string)$data['name']) . "', `image` = '" . $this->db->escape((string)$data['image']) . "', `sort_order` = '" . (int)$data['sort_order'] . "'");

		$manufacturer_id = $this->db->getLastId();

		// Store
		if (isset($data['manufacturer_store'])) {
			foreach ($data['manufacturer_store'] as $store_id) {
				$this->addStore($manufacturer_id, $store_id);
			}
		}

		// SEO URL
		$this->load->model('design/seo_url');

		foreach ($data['manufacturer_seo_url'] as $store_id => $language) {
			foreach ($language as $language_id => $keyword) {
				$this->model_design_seo_url->addSeoUrl($store_id, $language_id, 'manufacturer_id', $manufacturer_id, $keyword);
			}
		}

		// Layouts
		if (isset($data['manufacturer_layout'])) {
			foreach ($data['manufacturer_layout'] as $store_id => $layout_id) {
				if ($layout_id) {
					$this->addLayout($manufacturer_id, $store_id, $layout_id);
				}
			}
		}

		$this->cache->delete('manufacturer');

		return $manufacturer_id;
	}

	/**
	 * Edit Manufacturer
	 *
	 * @param int                  $manufacturer_id
	 * @param array<string, mixed> $data
	 *
	 * @return void
	 */
	public function editManufacturer(int $manufacturer_id, array $data): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "manufacturer` SET `name` = '" . $this->db->escape((string)$data['name']) . "', `image` = '" . $this->db->escape((string)$data['image']) . "', `sort_order` = '" . (int)$data['sort_order'] . "' WHERE `manufacturer_id` = '" . (int)$manufacturer_id . "'");

		// Store
		$this->deleteStore($manufacturer_id);

		if (isset($data['manufacturer_store'])) {
			foreach ($data['manufacturer_store'] as $store_id) {
				$this->addStore($manufacturer_id, $store_id);
			}
		}

		// SEO URL
		$this->load->model('design/seo_url');

		$this->model_design_seo_url->deleteSeoUrlByKeyValue('manufacturer_id', $manufacturer_id);

		if (isset($data['manufacturer_seo_url'])) {
			foreach ($data['manufacturer_seo_url'] as $store_id => $language) {
				foreach ($language as $language_id => $keyword) {
					$this->model_design_seo_url->addSeoUrl($store_id, $language_id, 'manufacturer_id', $manufacturer_id, $keyword);
				}
			}
		}

		// Layouts
		$this->deleteLayout($manufacturer_id);

		if (isset($data['manufacturer_layout'])) {
			foreach ($data['manufacturer_layout'] as $store_id => $layout_id) {
				if ($layout_id) {
					$this->addLayout($manufacturer_id, $store_id, $layout_id);
				}
			}
		}

		$this->cache->delete('manufacturer');
	}

	/**
	 * Delete Manufacturer
	 *
	 * @param int $manufacturer_id
	 *
	 * @return void
	 */
	public function deleteManufacturer(int $manufacturer_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "manufacturer` WHERE `manufacturer_id` = '" . (int)$manufacturer_id . "'");

		$this->deleteStore($manufacturer_id);
		$this->deleteLayout($manufacturer_id);

		$this->load->model('design/seo_url');

		$this->model_design_seo_url->deleteSeoUrlByKeyValue('manufacturer_id', $manufacturer_id);

		$this->cache->delete('manufacturer');
	}

	/**
	 * Get Manufacturer
	 *
	 * @param int $manufacturer_id
	 *
	 * @return array<string, mixed>
	 */
	public function getManufacturer(int $manufacturer_id): array {
		$query = $this->db->query("SELECT DISTINCT * FROM `" . DB_PREFIX . "manufacturer` WHERE `manufacturer_id` = '" . (int)$manufacturer_id . "'");

		return $query->row;
	}

	/**
	 * Get Manufacturers
	 *
	 * @param array<string, mixed> $data
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function getManufacturers(array $data = []): array {
		$sql = "SELECT * FROM `" . DB_PREFIX . "manufacturer`";

		if (!empty($data['filter_name'])) {
			$sql .= " WHERE LCASE(`name`) LIKE '" . $this->db->escape(oc_strtolower($data['filter_name']) . '%') . "'";
		}

		$sort_data = [
			'name',
			'sort_order'
		];

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY `name`";
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

		$query = $this->db->query($sql);

		return $query->rows;
	}

	/**
	 * Add Store
	 *
	 * @param int $information_id
	 * @param int $store_id
	 *
	 * @return void
	 */
	public function addStore(int $manufacturer_id, int $store_id): void {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "manufacturer_to_store` SET `manufacturer_id` = '" . (int)$manufacturer_id . "', `store_id` = '" . (int)$store_id . "'");
	}

	/**
	 * Delete Store
	 *
	 * @param int $information_id
	 *
	 * @return void
	 */
	public function deleteStore(int $manufacturer_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "manufacturer_to_store` WHERE `manufacturer_id` = '" . (int)$manufacturer_id . "'");
	}

	/**
	 * Get Stores
	 *
	 * @param int $manufacturer_id
	 *
	 * @return array<int, int>
	 */
	public function getStores(int $manufacturer_id): array {
		$manufacturer_store_data = [];

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "manufacturer_to_store` WHERE `manufacturer_id` = '" . (int)$manufacturer_id . "'");

		foreach ($query->rows as $result) {
			$manufacturer_store_data[] = $result['store_id'];
		}

		return $manufacturer_store_data;
	}

	/**
	 * Add Layout
	 *
	 * @param int $information_id
	 * @param int $store_id
	 * @param int $layout_id
	 *
	 * @return void
	 */
	public function addLayout(int $manufacturer_id, int $store_id, int $layout_id): void {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "manufacturer_to_layout` SET `manufacturer_id` = '" . (int)$manufacturer_id . "', store_id = '" . (int)$store_id . "', `layout_id` = '" . (int)$layout_id . "'");
	}

	/**
	 * Delete Store
	 *
	 * @param int $information_id
	 *
	 * @return void
	 */
	public function deleteLayout(int $manufacturer_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "manufacturer_to_layout` WHERE `manufacturer_id` = '" . (int)$manufacturer_id . "'");
	}

	public function deleteLayoutByLayoutId(int $layout_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "manufacturer_to_layout` WHERE `layout_id` = '" . (int)$layout_id . "'");
	}

	/**
	 * Get Layouts
	 *
	 * @param int $manufacturer_id
	 *
	 * @return array<int, int>
	 */
	public function getLayouts(int $manufacturer_id): array {
		$manufacturer_layout_data = [];

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "manufacturer_to_layout` WHERE `manufacturer_id` = '" . (int)$manufacturer_id . "'");

		foreach ($query->rows as $result) {
			$manufacturer_layout_data[$result['store_id']] = $result['layout_id'];
		}

		return $manufacturer_layout_data;
	}

	/**
	 * Get Total Manufacturers
	 *
	 * @return int
	 */
	public function getTotalManufacturers(): int {
		$query = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "manufacturer`");

		return (int)$query->row['total'];
	}

	/**
	 * Get Total Manufacturers By Layout ID
	 *
	 * @param int $layout_id
	 *
	 * @return int
	 */
	public function getTotalManufacturersByLayoutId(int $layout_id): int {
		$query = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "manufacturer_to_layout` WHERE `layout_id` = '" . (int)$layout_id . "'");

		return (int)$query->row['total'];
	}
}
