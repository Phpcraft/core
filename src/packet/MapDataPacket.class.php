<?php
namespace Phpcraft;
/** The packet sent to clients to update a map's contents and/or its markers. */
class MapDataPacket extends Packet
{
	private static function colors_1_12()
	{
		return [4=>[89,125,39],5=>[109,153,48],6=>[127,178,56],7=>[67,94,29],8=>[174,164,115],9=>[213,201,140],10=>[247,233,163],11=>[130,123,86],12=>[140,140,140],13=>[171,171,171],14=>[199,199,199],15=>[105,105,105],16=>[180,0,0],17=>[220,0,0],18=>[255,0,0],19=>[135,0,0],20=>[112,112,180],21=>[138,138,220],22=>[160,160,255],23=>[84,84,135],24=>[117,117,117],25=>[144,144,144],26=>[167,167,167],27=>[88,88,88],28=>[0,87,0],29=>[0,106,0],30=>[0,124,0],31=>[0,65,0],32=>[180,180,180],33=>[220,220,220],34=>[255,255,255],35=>[135,135,135],36=>[115,118,129],37=>[141,144,158],38=>[164,168,184],39=>[86,88,97],40=>[106,76,54],41=>[130,94,66],42=>[151,109,77],43=>[79,57,40],44=>[79,79,79],45=>[96,96,96],46=>[112,112,112],47=>[59,59,59],48=>[45,45,180],49=>[55,55,220],50=>[64,64,255],51=>[33,33,135],52=>[100,84,50],53=>[123,102,62],54=>[143,119,72],55=>[75,63,38],56=>[180,177,172],57=>[220,217,211],58=>[255,252,245],59=>[135,133,129],60=>[152,89,36],61=>[186,109,44],62=>[216,127,51],63=>[114,67,27],64=>[125,53,152],65=>[153,65,186],66=>[178,76,216],67=>[94,40,114],68=>[72,108,152],69=>[88,132,186],70=>[102,153,216],71=>[54,81,114],72=>[161,161,36],73=>[197,197,44],74=>[229,229,51],75=>[121,121,27],76=>[89,144,17],77=>[109,176,21],78=>[127,204,25],79=>[67,108,13],80=>[170,89,116],81=>[208,109,142],82=>[242,127,165],83=>[128,67,87],84=>[53,53,53],85=>[65,65,65],86=>[76,76,76],87=>[40,40,40],88=>[108,108,108],89=>[132,132,132],90=>[153,153,153],91=>[81,81,81],92=>[53,89,108],93=>[65,109,132],94=>[76,127,153],95=>[40,67,81],96=>[89,44,125],97=>[109,54,153],98=>[127,63,178],99=>[67,33,94],100=>[36,53,125],101=>[44,65,153],102=>[51,76,178],103=>[27,40,94],104=>[72,53,36],105=>[88,65,44],106=>[102,76,51],107=>[54,40,27],108=>[72,89,36],109=>[88,109,44],110=>[102,127,51],111=>[54,67,27],112=>[108,36,36],113=>[132,44,44],114=>[153,51,51],115=>[81,27,27],116=>[17,17,17],117=>[21,21,21],118=>[25,25,25],119=>[13,13,13],120=>[176,168,54],121=>[215,205,66],122=>[250,238,77],123=>[132,126,40],124=>[64,154,150],125=>[79,188,183],126=>[92,219,213],127=>[48,115,112],128=>[52,90,180],129=>[63,110,220],130=>[74,128,255],131=>[39,67,135],132=>[0,153,40],133=>[0,187,50],134=>[0,217,58],135=>[0,114,30],136=>[91,60,34],137=>[111,74,42],138=>[129,86,49],139=>[68,45,25],140=>[79,1,0],141=>[96,1,0],142=>[112,2,0],143=>[59,1,0],144=>[147,124,113],145=>[180,152,138],146=>[209,177,161],147=>[110,93,85],148=>[112,57,25],149=>[137,70,31],150=>[159,82,36],151=>[84,43,19],152=>[105,61,76],153=>[128,75,93],154=>[149,87,108],155=>[78,46,57],156=>[79,76,97],157=>[96,93,119],158=>[112,108,138],159=>[59,57,73],160=>[131,93,25],161=>[160,114,31],162=>[186,133,36],163=>[98,70,19],164=>[72,82,37],165=>[88,100,45],166=>[103,117,53],167=>[54,61,28],168=>[112,54,55],169=>[138,66,67],170=>[160,77,78],171=>[84,40,41],172=>[40,28,24],173=>[49,35,30],174=>[57,41,35],175=>[30,21,18],176=>[95,75,69],177=>[116,92,84],178=>[135,107,98],179=>[71,56,51],180=>[61,64,64],181=>[75,79,79],182=>[87,92,92],183=>[46,48,48],184=>[86,51,62],185=>[105,62,75],186=>[122,73,88],187=>[64,38,46],188=>[53,43,64],189=>[65,53,79],190=>[76,62,92],191=>[40,32,48],192=>[53,35,24],193=>[65,43,30],194=>[76,50,35],195=>[40,26,18],196=>[53,57,29],197=>[65,70,36],198=>[76,82,42],199=>[40,43,22],200=>[100,42,32],201=>[122,51,39],202=>[142,60,46],203=>[75,31,24],204=>[26,15,11],205=>[31,18,13],206=>[37,22,16],207=>[19,11,8]];
	}

	private static function colors_1_8_1()
	{
		return [4=>[89,125,39],5=>[109,153,48],6=>[127,178,56],7=>[67,94,29],8=>[174,164,115],9=>[213,201,140],10=>[247,233,163],11=>[130,123,86],12=>[138,138,138],13=>[169,169,169],14=>[197,197,197],15=>[104,104,104],16=>[180,0,0],17=>[220,0,0],18=>[255,0,0],19=>[135,0,0],20=>[112,112,180],21=>[138,138,220],22=>[160,160,255],23=>[84,84,135],24=>[117,117,117],25=>[144,144,144],26=>[167,167,167],27=>[88,88,88],28=>[0,87,0],29=>[0,106,0],30=>[0,124,0],31=>[0,65,0],32=>[180,180,180],33=>[220,220,220],34=>[255,255,255],35=>[135,135,135],36=>[115,118,129],37=>[141,144,158],38=>[164,168,184],39=>[86,88,97],40=>[129,74,33],41=>[157,91,40],42=>[183,106,47],43=>[96,56,24],44=>[79,79,79],45=>[96,96,96],46=>[112,112,112],47=>[59,59,59],48=>[101,84,51],49=>[123,103,62],50=>[143,119,72],51=>[76,63,38],52=>[45,45,180],53=>[55,55,220],54=>[64,64,255],55=>[33,33,135],56=>[180,177,172],57=>[220,217,211],58=>[255,252,245],59=>[135,133,129],60=>[152,89,36],61=>[186,109,44],62=>[216,127,51],63=>[114,67,27],64=>[125,53,152],65=>[153,65,186],66=>[178,76,216],67=>[94,40,114],68=>[72,108,152],69=>[88,132,186],70=>[102,153,216],71=>[54,81,114],72=>[161,161,36],73=>[197,197,44],74=>[229,229,51],75=>[121,121,27],76=>[89,144,17],77=>[109,176,21],78=>[127,204,25],79=>[67,108,13],80=>[170,89,116],81=>[208,109,142],82=>[242,127,165],83=>[128,67,87],84=>[53,53,53],85=>[65,65,65],86=>[76,76,76],87=>[40,40,40],88=>[108,108,108],89=>[132,132,132],90=>[153,153,153],91=>[81,81,81],92=>[53,89,108],93=>[65,109,132],94=>[76,127,153],95=>[40,67,81],96=>[89,44,125],97=>[109,54,153],98=>[127,63,178],99=>[67,33,94],100=>[36,53,125],101=>[44,65,153],102=>[51,76,178],103=>[27,40,94],104=>[72,53,36],105=>[88,65,44],106=>[102,76,51],107=>[54,40,27],108=>[72,89,36],109=>[88,109,44],110=>[102,127,51],111=>[54,67,27],112=>[108,36,36],113=>[132,44,44],114=>[153,51,51],115=>[81,27,27],116=>[17,17,17],117=>[21,21,21],118=>[25,25,25],119=>[13,13,13],120=>[176,168,54],121=>[215,205,66],122=>[250,238,77],123=>[132,126,40],124=>[64,154,150],125=>[79,188,183],126=>[92,219,213],127=>[48,115,112],128=>[52,90,180],129=>[63,110,220],130=>[74,128,255],131=>[39,67,135],132=>[0,153,40],133=>[0,187,50],134=>[0,217,58],135=>[0,114,30],136=>[90,59,34],137=>[110,73,41],138=>[127,85,48],139=>[67,44,25],140=>[79,1,0],141=>[96,1,0],142=>[112,2,0],143=>[59,1,0]];
	}

	/**
	 * Gets the color ID closest to the given RGB array (3 integers).
	 * @param $rgb integer[]
	 * @param boolean $new_colors Ignore this parameter.
	 * @return integer
	 */
	public static function getColorId(array $rgb, bool $new_colors = true)
	{
		$best_color = $best_diff = 0;
		foreach(($new_colors ? MapDataPacket::colors_1_12() : MapDataPacket::colors_1_8_1()) as $id => $rgb2)
		{
			$diff = Phpcraft::colorDiff($rgb, $rgb2);
			if($best_color == 0 || $diff < $best_diff)
			{
				$best_color = $id;
				$best_diff = $diff;
			}
		}
		return $best_color;
	}

	/**
	 * The map ID.
	 * See https://minecraft.gamepedia.com/Map#Item_data for the NBT data the map needs.
	 * @var integer $mapId
	 */
	public $mapId = 0;
	/**
	 * The scale of the map from a from 0 for a fully zoomed-in map (1 block per pixel) to 4 for a fully zoomed-out map (16 blocks per pixel). This is only shown when hovering over the map item, and doesn't effect rendering of the map when held.
	 * @var integer $scale
	 */
	public $scale = 0;
	/**
	 * A MapMarkers array of markers on the map.
	 * @var array $markers
	 */
	public $markers = [];
	/**
	 * The x coordinate on the map at which the content update starts.
	 * @var integer $x
	 */
	public $x = 0;
	/**
	 * The z coordinate on the map at which the content update starts.
	 * @var integer $z
	 */
	public $z = 0;
	/**
	 * The width of the updated area.
	 * @var integer $width
	 */
	public $width = 0;
	/**
	 * The height of the updated area.
	 * @var integer $height
	 */
	public $height = 0;
	/**
	 * The contents of the map. This should have $width * $height elements.
	 * @see MapDataPacket::getColorId
	 * @var array $contents
	 */
	public $contents = [];

	/**
	 * Initialises the packet class by reading its payload from the given Connection.
	 * @param Connection $con
	 * @return MapDataPacket
	 * @throws IOException
	 */
	public static function read(Connection $con)
	{
		$packet = new MapDataPacket();
		$packet->mapId = $con->readVarInt();
		$packet->scale = $con->readByte();
		if($con->protocol_version > 47)
		{
			$con->ignoreBytes(1); // Tracking Position
			if($con->protocol_version >= 472)
			{
				$con->ignoreBytes(1); // Locked
			}
		}
		$markers_i = $con->readVarInt();
		for($i = 0; $i < $markers_i; $i++)
		{
			$marker = new MapMarker();
			if($con->protocol_version >= 373)
			{
				$marker->type = $con->readVarInt();
				$marker->x = $con->readByte();
				$marker->z = $con->readByte();
				$marker->rotation = $con->readByte();
				if($con->protocol_version >= 364 && $con->readBoolean())
				{
					$marker->name = $con->readChat();
				}
			}
			else
			{
				$type = $con->readByte();
				$marker->type = $type >> 4;
				$marker->rotation = $type & 0x0F;
				$marker->x = $con->readByte();
				$marker->z = $con->readByte();
			}
			array_push($packet->markers, $marker);
		}
		if(($packet->width = $con->readByte()) > 0)
		{
			$packet->height = $con->readByte();
			$packet->x = $con->readByte();
			$packet->z = $con->readByte();
			$contents_i = $con->readVarInt();
			if($con->protocol_version > 47)
			{
				for($i = 0; $i < $contents_i; $i++)
				{
					array_push($packet->contents, $con->readByte());
				}
			}
			else
			{
				$colors = MapDataPacket::colors_1_8_1();
				for($i = 0; $i < $contents_i; $i++)
				{
					$colorId = $con->readByte();
					if(in_array($colorId, $colors))
					{
						array_push($packet->contents, MapDataPacket::getColorId($colors[$colorId]));
					}
					else
					{
						array_push($packet->contents, 0);
					}
				}
			}
		}
		return $packet;
	}

	/**
	 * Adds the packet's ID and payload to the Connection's write buffer and, if the connection has a stream, sends it over the wire.
	 * @param Connection $con
	 * @throws IOException
	 */
	public function send(Connection $con)
	{
		$con->startPacket("map_data");
		$con->writeVarInt($this->mapId);
		$con->writeByte($this->scale);
		if($con->protocol_version > 47)
		{
			$con->writeBoolean(true); // Tracking Position
			if($con->protocol_version >= 472)
			{
				$con->writeBoolean(true); // Locked
			}
		}
		$con->writeVarInt(count($this->markers));
		foreach($this->markers as $marker)
		{
			if($con->protocol_version >= 373)
			{
				$con->writeByte($marker->type);
				$con->writeByte($marker->x);
				$con->writeByte($marker->z);
				$con->writeByte($marker->rotation);
				if($con->protocol_version >= 364)
				{
					if($marker->name)
					{
						$con->writeBoolean(true);
						$con->writeChat($marker->name);
					}
					else
					{
						$con->writeBoolean(false);
					}
				}
			}
			else
			{
				$type = $marker->type;
				if($type > 9)
				{
					$type = 7;
				}
				$con->writeByte($type << 4 | $marker->rotation);
				$con->writeByte($marker->x);
				$con->writeByte($marker->z);
			}
		}
		if(empty($this->contents))
		{
			$con->writeVarInt(0);
		}
		else
		{
			$con->writeByte($this->width);
			$con->writeByte($this->height);
			$con->writeByte($this->x);
			$con->writeByte($this->z);
			$con->writeVarInt(count($this->contents));
			if($con->protocol_version > 47)
			{
				foreach($this->contents as $colorId)
				{
					$con->writeByte($colorId);
				}
			}
			else
			{
				$colors = MapDataPacket::colors_1_12();
				foreach($this->contents as $colorId)
				{
					$con->writeByte(MapDataPacket::getColorId($colors[$colorId], false));
				}
			}
		}
		$con->send();
	}

	public function __toString()
	{
		$str = "{Map Data: Map ID ".$this->mapId.", Scale ".$this->scale.", {$this->width}x{$this->height} Pixels, From {$this->x}:{$this->z}, Markers:";
		if(empty($this->markers))
		{
			return $str." None}";
		}
		foreach($this->markers as $marker)
		{
			$str .= " ".$marker->__toString();
		}
		return $str."}";
	}
}
