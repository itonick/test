package com.internousdev.ecsite.dao;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;


import com.internousdev.ecsite.dto.ItemDetailDTO;
import com.internousdev.ecsite.util.DBConnector;

public class ItemDetailDAO {

	public ItemDetailDTO getItemInfoList(String id) {
		DBConnector dbConnector = new DBConnector();
		Connection connection = dbConnector.getConnection();
		ItemDetailDTO itemDetailDTO = new ItemDetailDTO();
		String sql = "select * from item_info_transaction where id=?";

		try {
			PreparedStatement preparedStatement = connection.prepareStatement(sql);
			preparedStatement.setString(1, id);
			ResultSet resultSet = preparedStatement.executeQuery();
			if (resultSet.next()) {

				itemDetailDTO.setId(resultSet.getInt("id"));
				itemDetailDTO.setItemName(resultSet.getString("item_name"));
				itemDetailDTO.setItemPrice(resultSet.getString("item_price"));
				itemDetailDTO.setItemStock(resultSet.getString("item_stock"));
				itemDetailDTO.setInsert_date(resultSet.getString("insert_date"));

			}
		} catch (SQLException e) {
			e.printStackTrace();
		}
		try {
			connection.close();
		} catch (SQLException e) {
			e.printStackTrace();
		}
		return itemDetailDTO;
	}




}
