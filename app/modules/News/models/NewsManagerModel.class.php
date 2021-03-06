<?php

class News_NewsManagerModel extends XRXNewsBaseModel implements XRXICategoryModel
{
	public function retrieveById($id, $language = null)
	{
		if (empty ($id))
			return null;

		$sql = "SELECT
					n.*,
					ni.*,
					c.name AS category_name,
					u.username
				FROM %s AS n
				LEFT JOIN %s AS ni ON (n.id = ni.news_id)
				LEFT JOIN %s AS c ON (n.category_id = c.id)
				LEFT JOIN %s AS u ON (n.author_id = u.id)
				WHERE n.id = :id ";

		if (isset ($language)) {
			$sql .= "AND ni.language = :language";
		}

		$sql	= sprintf($sql, self::NEWS, self::NEWS_I18N, self::CATEGORIES, self::USERS);
		$stmt	= $this->getContext()->getDatabaseConnection()->prepare($sql);
		$stmt->bindValue(':id', $id, PDO::PARAM_INT);

		if (isset ($language)) {
			$stmt->bindValue(':language', $language, PDO::PARAM_STR);
		}
		
		$stmt->execute();
		$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

		// No record?
		if (! $result) {
			return null;
		}
		
		foreach ($result as $n) {
			$t = array(
				'username'		=> $n['username'],
				'category_name'	=> $n['category_name']
			);
			
			$t = array_merge($t, $this->getContext()->getModel('News', 'News', array($n))->toArray());
			$t = array_merge($t, $this->getContext()->getModel('NewsI18n', 'News', array($n))->toArray());
			
			$news[$t['language']] = (object) $t;
		}
		
		return $news;
	}


	public function retrieveLatest($language = null, $limit = 10, $start = 0, $published = null, $category_id = null)
	{
		$limit = (integer) $limit;
		$start = (integer) $start;

		try {
			$sql = "SELECT
						SQL_CALC_FOUND_ROWS *,
						n.*,
						ni.*,
						c.name AS category_name,
						u.username
					FROM %s AS n
					LEFT JOIN %s AS ni ON (n.id = ni.news_id)
					LEFT JOIN %s AS c ON (n.category_id = c.id)
					LEFT JOIN %s AS u ON (n.author_id = u.id) ";

			// Language
			if (isset ($language)) {
				$sql.= "WHERE ni.language = :language ";
			}

			// Published
			if (isset ($published)) {
				$sql .= (strpos($sql, "WHERE") != false) ? "AND " : "WHERE ";
				$sql .= "n.published = :published ";
			}

			// Category
			if (isset ($category_id)) {
				$sql .= (strpos($sql, "WHERE") != false) ? "AND " : "WHERE ";
				$sql .= "n.category_id = :category_id ";
			}
			
			$sql .= "ORDER BY n.date DESC
					 LIMIT :start, :limit";


			$sql	= sprintf($sql, self::NEWS, self::NEWS_I18N, self::CATEGORIES, self::USERS);
			$stmt	= $this->getContext()->getDatabaseConnection()->prepare($sql);
			$stmt->bindValue(':start', $start, PDO::PARAM_INT);
			$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);

			if (isset ($language)) {
				$stmt->bindValue(':language', $language, PDO::PARAM_STR);
			}

			if (isset ($published)) {
				$stmt->bindValue(':published', $published, PDO::PARAM_BOOL);
			}

			if (isset ($category_id)) {
				$stmt->bindValue(':category_id', $category_id, PDO::PARAM_INT);
			}


			$stmt->execute();
			$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

			// No record?
			if (! $result) {
				return null;
			}
			
			foreach ($result as $n) {
				$t = array(
					'username'		=> $n['username'],
					'category_name'	=> $n['category_name']
				);

				$t = array_merge($t, $this->getContext()->getModel('NewsI18n', 'News', array($n))->toArray());
				$t = array_merge($t, $this->getContext()->getModel('News', 'News', array($n))->toArray());
				$news[] = (object) $t;
			}
			
			return $news;
		}
		catch (PDOException $e) {
			throw new AgaviDatabaseException($e->getMessage());
		}
	}
	

	public function create(array $data)
	{
		$news		= $this->getContext()->getModel('News', 'News', array($data));
		$newsI18n	= array();

		foreach ($data['translations'] as $field) {
			$newsI18n[] = $this->getContext()->getModel('NewsI18n', 'News', array($field));
		}
		
		try {
			// Start Transaction
			$this->getContext()->getDatabaseConnection()->beginTransaction();


			// 1. Insert data into News Table
			$sql = "INSERT INTO %s (date, published, comment_status, image, author_id, category_id)
					VALUES(:date, :published, :comment_status, :image, :author_id, :category_id)";

			$sql	= sprintf($sql, self::NEWS);
			$stmt	= $this->getContext()->getDatabaseConnection()->prepare($sql);
			$stmt->bindValue(':date', $news->getDate(), PDO::PARAM_STR);
			$stmt->bindValue(':published', $news->getPublished(), PDO::PARAM_BOOL);
			$stmt->bindValue(':comment_status', $news->getCommentStatus(), PDO::PARAM_BOOL);
			$stmt->bindValue(':image', $news->getImage(), PDO::PARAM_STR);
			$stmt->bindValue(':author_id', $news->getAuthorId(), PDO::PARAM_INT);
			$stmt->bindValue(':category_id', $news->getCategoryId(), PDO::PARAM_INT);
			$stmt->execute();

			// Sync ORM's with new inserted id
			$news->setId( $this->getContext()->getDatabaseConnection()->lastInsertId() );


			// 2. Insert data into News_I18N Table
			$sql = "INSERT INTO %s (news_id, title, summary, content, language)
					VALUES";

			$sql = sprintf($sql, self::NEWS_I18N);

			// Create Values clause for each language
			foreach ($newsI18n as $ni) {
				$l		= $ni->getLanguage();
				$temp[] = "(:news_id_$l, :title_$l, :summary_$l, :content_$l, :language_$l)";

				// To prevent extra loop, it's better to update id here
				$ni->setNewsId( $news->getId() );
			}

			// Append created statement to base sql
			$sql .= implode(',', $temp);
			$stmt = $this->getContext()->getDatabaseConnection()->prepare($sql);
			
			// Add values for each language in PDO way
			foreach ($newsI18n as $ni) {
				$l = $ni->getLanguage();
				
				$stmt->bindValue(":news_id_$l", $ni->getNewsId(), PDO::PARAM_INT);
				$stmt->bindValue(":title_$l", $ni->getTitle(), PDO::PARAM_STR);
				$stmt->bindValue(":summary_$l", $ni->getSummary(), PDO::PARAM_STR);
				$stmt->bindValue(":content_$l", $ni->getContent(), PDO::PARAM_STR);
				$stmt->bindValue(":language_$l", $ni->getLanguage(), PDO::PARAM_STR);
			}
			
			$stmt->execute();

			// Finally Commit the Transaction
			$this->getContext()->getDatabaseConnection()->commit();
		}
		catch (PDOException $e) {
			$this->getContext()->getDatabaseConnection()->rollBack();
			throw new AgaviDatabaseException($e->getMessage());
		}

		return true;
	}


	public function update(array $data)
	{
		$news		= $this->getContext()->getModel('News', 'News', array($data));
		$newsI18n	= array();

		foreach ($data['translations'] as $field) {
			$field['news_id'] = $news->getId();
			$newsI18n[] = $this->getContext()->getModel('NewsI18n', 'News', array($field));
		}
		
		try {
			// Start Transaction
			$this->getContext()->getDatabaseConnection()->beginTransaction();

			// 1. Update News Table data
			$sql = "UPDATE %s AS n
					SET n.modified = :modified,
						n.published = :published,
						n.comment_status = :comment_status,
						n.image = :image,
						n.category_id = :category_id
					WHERE n.id = :id";

			$sql	= sprintf($sql, self::NEWS);
			$stmt	= $this->getContext()->getDatabaseConnection()->prepare($sql);
			$stmt->bindValue(':id', $news->getId(), PDO::PARAM_INT);
			$stmt->bindValue(':modified', $news->getModified(), PDO::PARAM_STR);
			$stmt->bindValue(':published', $news->getPublished(), PDO::PARAM_BOOL);
			$stmt->bindValue(':comment_status', $news->getCommentStatus(), PDO::PARAM_BOOL);
			$stmt->bindValue(':image', $news->getImage(), PDO::PARAM_STR);
			$stmt->bindValue(':category_id', $news->getCategoryId(), PDO::PARAM_INT);
			$stmt->execute();
			
			
			// Update each language
			foreach ($newsI18n as $ni) {
				// 2. Insert/Update data into News_I18N Table
				$sql = "INSERT INTO %s (news_id, title, summary, content, language)
						VALUES (:news_id, :title, :summary, :content, :language)
						ON DUPLICATE KEY UPDATE 
							title = VALUES(title),
							summary = VALUES(summary),
							content = VALUES(content)";
				
				$sql	= sprintf($sql, self::NEWS_I18N);
				$stmt	= $this->getContext()->getDatabaseConnection()->prepare($sql);
				$stmt->bindValue(':news_id', $ni->getNewsId(), PDO::PARAM_INT);
				$stmt->bindValue(':title', $ni->getTitle(), PDO::PARAM_STR);
				$stmt->bindValue(':summary', $ni->getSummary(), PDO::PARAM_STR);
				$stmt->bindValue(':content', $ni->getContent(), PDO::PARAM_STR);
				$stmt->bindValue(':language', $ni->getLanguage(), PDO::PARAM_STR);
				$stmt->execute();
			}
			
			// Finally Commit the Transaction
			$this->getContext()->getDatabaseConnection()->commit();
		}
		catch (PDOException $e) {
			$this->getContext()->getDatabaseConnection()->rollBack();
			throw new AgaviDatabaseException($e->getMessage());
		}

		return true;
	}


	public function deleteById($id)
	{
		if (empty ($id))
			return false;

		try {
			$sql = "DELETE n FROM %s AS n
					WHERE n.id = :id";

			$sql	= sprintf($sql, self::NEWS);
			$stmt	= $this->getContext()->getDatabaseConnection()->prepare($sql);
			$stmt->bindValue(':id', $id, PDO::PARAM_INT);
			$stmt->execute();
		}
		catch (PDOException $e) {
			throw new AgaviDatabaseException($e->getMessage());
		}

		return true;
	}


	public function deleteAllById(array $id)
	{
		if (empty ($id))
			return false;

		try {
			$sql = "DELETE n FROM %s AS n
					WHERE n.id IN (%s)";

			// TODO: Change the way language appended to query!
			$id		= "'" . implode("','", $id) . "'";
			$sql	= sprintf($sql, self::NEWS, $id);
			$stmt	= $this->getContext()->getDatabaseConnection()->prepare($sql);
			$stmt->execute();
		}
		catch (PDOException $e) {
			throw new AgaviDatabaseException($e->getMessage());
		}

		return true;
	}


	public function deleteByLang($id, $language)
	{
		if (empty ($id) || empty ($language))
			return false;

		try {
			$sql = "DELETE ni FROM %s AS ni
					WHERE ni.news_id = :id AND ni.language = :language";

			$sql	= sprintf($sql, self::NEWS_I18N);
			$stmt	= $this->getContext()->getDatabaseConnection()->prepare($sql);
			$stmt->bindValue(':id', $id, PDO::PARAM_INT);
			$stmt->bindValue(':language', $language, PDO::PARAM_STR);
			$stmt->execute();
		}
		catch (PDOException $e) {
			throw new AgaviDatabaseException($e->getMessage());
		}

		return true;
	}


	public function deleteAllByLang($id, array $language)
	{
		if (empty ($id) || empty ($language))
			return false;

		try {
			$sql = "DELETE ni FROM %s AS ni
					WHERE ni.news_id = :id AND ni.language IN (%s)";

			// TODO: Change the way language appended to query!
			$language	= "'" . implode("','", $language) . "'";
			$sql		= sprintf($sql, self::NEWS_I18N, $language);
			$stmt		= $this->getContext()->getDatabaseConnection()->prepare($sql);
			$stmt->bindValue(':id', $id, PDO::PARAM_INT);
			$stmt->execute();
		}
		catch (PDOException $e) {
			throw new AgaviDatabaseException($e->getMessage());
		}

		return true;
	}


	// Implements XRXICategoryModel.interface.php
	public function resetCategoryId()
	{
		try {
			$sql = "UPDATE %s AS n
					SET n.category_id = 1
					WHERE n.category_id IS NULL";

			$sql	= sprintf($sql, self::NEWS);
			$stmt	= $this->getContext()->getDatabaseConnection()->prepare($sql);
			$stmt->execute();
		}
		catch (PDOException $e) {
			throw new AgaviDatabaseException($e->getMessage());
		}

		return true;
	}
}

?>