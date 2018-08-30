package com.internousdev.ecsite.dao;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.SQLException;

import com.internousdev.ecsite.util.DBConnector;

public class ItemDeleteDAO {

	private DBConnector dbConnector=new DBConnector();
	private Connection connection=dbConnector.getConnection();

	public int itemDelete(String item_name,String item_price,String item_stock) throws SQLException{
		String sql="DELETE FROM item_info_transaction where id";

		PreparedStatement preparedStatement;
		int result=0;
		try{
			preparedStatement=connection.prepareStatement(sql);
			preparedStatement.setString(1,item_name);
			preparedStatement.setString(2,item_price);
			preparedStatement.setString(3,item_stock);
			result=preparedStatement.executeUpdate();
		}catch(SQLException e){
			e.printStackTrace();
		}finally {
			connection.close();
		}
		return result;
	}

}
