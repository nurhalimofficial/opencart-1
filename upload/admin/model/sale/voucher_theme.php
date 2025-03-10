<?php
namespace Opencart\Admin\Model\Sale;
/**
 * Class Voucher Theme
 *
 * @package Opencart\Admin\Model\Sale
 */
class VoucherTheme extends \Opencart\System\Engine\Model {
	/**
	 * Add Voucher Theme
	 *
	 * @param array<string, mixed> $data
	 *
	 * @return int
	 */
	public function addVoucherTheme(array $data): int {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "voucher_theme` SET `image` = '" . $this->db->escape((string)$data['image']) . "'");

		$voucher_theme_id = $this->db->getLastId();

		foreach ($data['voucher_theme_description'] as $language_id => $value) {
			$this->addDescription($voucher_theme_id, $language_id, $value);
		}

		$this->cache->delete('voucher_theme');

		return $voucher_theme_id;
	}

	/**
	 * Edit Voucher Theme
	 *
	 * @param int                  $voucher_theme_id
	 * @param array<string, mixed> $data
	 *
	 * @return void
	 */
	public function editVoucherTheme(int $voucher_theme_id, array $data): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "voucher_theme` SET `image` = '" . $this->db->escape((string)$data['image']) . "' WHERE `voucher_theme_id` = '" . (int)$voucher_theme_id . "'");

		$this->deleteDescription($voucher_theme_id);

		foreach ($data['voucher_theme_description'] as $language_id => $value) {
			$this->addDescription($voucher_theme_id, $language_id, $value);
		}

		$this->cache->delete('voucher_theme');
	}

	/**
	 * Delete Voucher Theme
	 *
	 * @param int $voucher_theme_id
	 *
	 * @return void
	 */
	public function deleteVoucherTheme(int $voucher_theme_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "voucher_theme` WHERE `voucher_theme_id` = '" . (int)$voucher_theme_id . "'");

		$this->deleteDescription($voucher_theme_id);

		$this->cache->delete('voucher_theme');
	}

	/**
	 * Get Voucher Theme
	 *
	 * @param int $voucher_theme_id
	 *
	 * @return array<string, mixed>
	 */
	public function getVoucherTheme(int $voucher_theme_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "voucher_theme` `vt` LEFT JOIN `" . DB_PREFIX . "voucher_theme_description` `vtd` ON (`vt`.`voucher_theme_id` = `vtd`.`voucher_theme_id`) WHERE `vt`.`voucher_theme_id` = '" . (int)$voucher_theme_id . "' AND `vtd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "'");

		return $query->row;
	}

	/**
	 * Get Voucher Themes
	 *
	 * @param array<string, mixed> $data
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function getVoucherThemes(array $data = []): array {
		$sql = "SELECT * FROM `" . DB_PREFIX . "voucher_theme` `vt` LEFT JOIN `" . DB_PREFIX . "voucher_theme_description` `vtd` ON (`vt`.`voucher_theme_id` = `vtd`.`voucher_theme_id`) WHERE `vtd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "' ORDER BY `vtd`.`name`";

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

		$voucher_theme_data = $this->cache->get('voucher_theme.' . $key);

		if (!$voucher_theme_data) {
			$query = $this->db->query($sql);

			$voucher_theme_data = $query->rows;

			$this->cache->set('voucher_theme.' . $key, $voucher_theme_data);
		}

		return $voucher_theme_data;
	}

	/**
	 *	Add Description
	 *
	 *
	 * @param int $attribute_id primary key of the attribute record to be fetched
	 *
	 * @return array<int, array<string, string>> Descriptions sorted by language_id
	 */
	public function addDescription(int $voucher_theme_id, int $language_id, $data): void {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "voucher_theme_description` SET `voucher_theme_id` = '" . (int)$voucher_theme_id . "', `language_id` = '" . (int)$language_id . "', `name` = '" . $this->db->escape($data['name']) . "'");
	}

	/**
	 *	Delete Description
	 *
	 *
	 * @param int $attribute_id primary key of the attribute record to be fetched
	 *
	 * @return array<int, array<string, string>> Descriptions sorted by language_id
	 */
	public function deleteDescription(int $voucher_theme_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "voucher_theme_description` WHERE `voucher_theme_id` = '" . (int)$voucher_theme_id . "'");
	}

	/**
	 * Get Descriptions
	 *
	 * @param int $voucher_theme_id
	 *
	 * @return array<int, array<string, string>>
	 */
	public function getDescriptions(int $voucher_theme_id): array {
		$voucher_theme_data = [];

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "voucher_theme_description` WHERE `voucher_theme_id` = '" . (int)$voucher_theme_id . "'");

		foreach ($query->rows as $result) {
			$voucher_theme_data[$result['language_id']] = ['name' => $result['name']];
		}

		return $voucher_theme_data;
	}

	/**
	 * Get Total Voucher Themes
	 *
	 * @return int
	 */
	public function getTotalVoucherThemes(): int {
		$query = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "voucher_theme`");

		return (int)$query->row['total'];
	}
}
