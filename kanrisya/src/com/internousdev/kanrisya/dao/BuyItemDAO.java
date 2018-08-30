package com.internousdev.kanrisya.dao;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import com.internousdev.kanrisya.dto.BuyItemDTO;
import com.internousdev.kanrisya.util.DBConnector;

public class BuyItemDAO {
	private DBConnector dbConnector=new DBConnector();
	private BuyItemDTO buyItemDTO=new BuyItemDTO();
	private Connection connection=dbConnector.getConnection();

	public BuyItemDTO getBuyItemInfo(){
		String sql="SELECT id,item_name,item_price FROM item_info_transaction";
		try{
			PreparedStatement preparedStatement=connection.prepareStatement(sql);
			ResultSet resultSet=preparedStatement.executeQuery();
			if(resultSet.next()){
				buyItemDTO.setId(resultSet.getInt("id"));
				buyItemDTO.setItemName(resultSet.getString("item_name"));
				buyItemDTO.setItemPrice(resultSet.getString("item_price"));
			}
		}catch(Exception e){
			e.printStackTrace();
		}
		return buyItemDTO;
	}

	public BuyItemDTO getBuyItemDTO(){
		return buyItemDTO;
	}

}
