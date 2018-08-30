package com.internousdev.ecsite.dao;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.ArrayList;
import com.internousdev.ecsite.dto.ItemListDTO;
import com.internousdev.ecsite.util.DBConnector;

public class ItemListDAO {

	private DBConnector dbConnector=new DBConnector();
	private Connection connection=dbConnector.getConnection();

	public ArrayList<ItemListDTO> getItemInfo() throws SQLException{
		ArrayList<ItemListDTO> itemListDTO=new ArrayList<ItemListDTO>();
		String sql=
				"SELECT id,item_name,item_price,item_stock,insert_date FROM item_info_transaction ORDER BY insert_date";
		try{
			PreparedStatement preparedStatement=connection.prepareStatement(sql);

			ResultSet resultSet=preparedStatement.executeQuery();

			while(resultSet.next()){
				ItemListDTO dto=new ItemListDTO();
				dto.setId(resultSet.getString("id"));
				dto.setItemName(resultSet.getString("item_name"));
				dto.setItemPrice(resultSet.getString("item_price"));
				dto.setItemStock(resultSet.getString("item_stock"));
				dto.setInsert_date(resultSet.getString("insert_date"));
				itemListDTO.add(dto);
			}
		}catch(Exception e){
			e.printStackTrace();
		}finally {
			connection.close();
		}
		return itemListDTO;
	}

	public int itemHistoryDelete(String item_name,String item_price,String item_stock) throws SQLException{
		String sql="DELETE FROM item_info_transaction";

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
