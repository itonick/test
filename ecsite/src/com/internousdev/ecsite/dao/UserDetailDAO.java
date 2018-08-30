package com.internousdev.ecsite.dao;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;

import com.internousdev.ecsite.dto.UserDetailDTO;
import com.internousdev.ecsite.util.DBConnector;

public class UserDetailDAO {
	public UserDetailDTO getUserInfoList(String id) {
		DBConnector dbConnector = new DBConnector();
		Connection connection = dbConnector.getConnection();
		UserDetailDTO userDetailDTO = new UserDetailDTO();
		String sql = "select * from login_user_transaction where id=?";

		try {
			PreparedStatement preparedStatement = connection.prepareStatement(sql);
			preparedStatement.setString(1, id);
			ResultSet resultSet = preparedStatement.executeQuery();
			if (resultSet.next()) {

				userDetailDTO.setId(resultSet.getInt("id"));
				userDetailDTO.setLoginId(resultSet.getString("login_id"));
				userDetailDTO.setLoginPass(resultSet.getString("login_pass"));
				userDetailDTO.setUserName(resultSet.getString("user_name"));
				userDetailDTO.setInsert_date(resultSet.getString("insert_date"));

				System.out.println(id);


			}
		} catch (SQLException e) {
			e.printStackTrace();
		}
		try {
			connection.close();
		} catch (SQLException e) {
			e.printStackTrace();
		}
		return userDetailDTO;
	}

}
